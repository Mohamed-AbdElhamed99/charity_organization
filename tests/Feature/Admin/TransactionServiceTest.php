<?php

namespace Tests\Feature\Admin;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionServiceInterface $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->transactionService = app(TransactionServiceInterface::class);
    }

    private function createStaffUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        return $user;
    }

    public function test_reverse_transaction_creates_compensating_entry(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $original = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'direction' => TransactionDirection::Out,
            'gross_amount' => 500.00,
            'fee_amount' => 0,
            'net_amount' => 500.00,
            'running_balance' => bcsub((string) $account->opening_balance, '500.00', 2),
            'created_by' => $user->id,
        ]);

        $transfer = Transfer::factory()->toLabel('Vendor Co')->create([
            'transaction_id' => $original->id,
            'amount' => 500.00,
            'created_by' => $user->id,
        ]);

        $reversal = $this->transactionService->reverseTransaction($original, $user->id);

        $this->assertDatabaseHas('transactions', [
            'id' => $reversal->id,
            'transaction_type' => TransactionType::Adjustment->value,
            'direction' => TransactionDirection::In->value,
            'account_id' => $account->id,
            'net_amount' => '500.00',
            'created_by' => $user->id,
        ]);

        $this->assertSoftDeleted('transfers', ['id' => $transfer->id]);

        $expectedBalance = (string) $account->opening_balance;
        $this->assertSame($expectedBalance, (string) $reversal->fresh()->running_balance);
    }

    public function test_reverse_transaction_rejects_adjustment_entries(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $adjustment = Transaction::factory()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_type' => TransactionType::Adjustment,
            'direction' => TransactionDirection::In,
            'gross_amount' => 100.00,
            'fee_amount' => 0,
            'net_amount' => 100.00,
            'running_balance' => bcadd((string) $account->opening_balance, '100.00', 2),
            'created_by' => $user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->transactionService->reverseTransaction($adjustment, $user->id);
    }

    public function test_transfer_type_transaction_creates_morph_or_label_detail(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Transfer,
            direction: TransactionDirection::Out,
            grossAmount: 1250.50,
            feeAmount: 0,
            transactionDate: now()->toDateString(),
            referenceNumber: null,
            description: null,
            notes: 'Paid by cheque',
            paymentMethodId: null,
            createdBy: $user->id,
            transfer: [
                'recipient_kind' => 'other',
                'recipient_label' => 'ABC Supplies Co.',
                'purpose' => 'Food boxes purchase',
                'notes' => 'Paid by cheque',
            ],
        ));

        $this->assertDatabaseHas('transfers', [
            'transaction_id' => $transaction->id,
            'recipient_type' => null,
            'recipient_id' => null,
            'recipient_label' => 'ABC Supplies Co.',
            'amount' => '1250.50',
            'purpose' => 'Food boxes purchase',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'account_id' => $account->id,
            'transaction_type' => TransactionType::Transfer->value,
            'direction' => TransactionDirection::Out->value,
            'currency_id' => $account->currency_id,
            'net_amount' => '1250.50',
        ]);

        $expectedBalance = bcsub((string) $account->opening_balance, '1250.50', 2);
        $this->assertSame($expectedBalance, (string) $transaction->running_balance);
    }

    public function test_transfer_to_user_uses_morph_recipient(): void
    {
        $user = $this->createStaffUser();
        $recipient = User::factory()->create(['name' => 'Staff Member']);
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Transfer,
            direction: TransactionDirection::Out,
            grossAmount: 300,
            feeAmount: 0,
            transactionDate: now()->toDateString(),
            referenceNumber: null,
            description: null,
            notes: null,
            paymentMethodId: null,
            createdBy: $user->id,
            transfer: [
                'recipient_kind' => 'user',
                'recipient_id' => $recipient->id,
                'purpose' => 'Staff reimbursement',
            ],
        ));

        $this->assertDatabaseHas('transfers', [
            'transaction_id' => $transaction->id,
            'recipient_type' => User::class,
            'recipient_id' => $recipient->id,
            'recipient_label' => null,
        ]);
    }

    public function test_fx_converts_original_amount_into_account_currency(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $egp = Currency::query()->where('code', 'EGP')->firstOrFail();

        $account->update(['currency_id' => $egp->id]);

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Transfer,
            direction: TransactionDirection::Out,
            grossAmount: 1000,
            feeAmount: 0,
            transactionDate: '2026-02-11',
            referenceNumber: null,
            description: null,
            notes: null,
            paymentMethodId: null,
            createdBy: $user->id,
            originalCurrencyId: $usd->id,
            originalAmount: 1000,
            exchangeRate: 52,
            transfer: [
                'recipient_kind' => 'user',
                'recipient_id' => $user->id,
                'purpose' => 'FX transfer',
            ],
        ));

        $this->assertSame('52000.00', (string) $transaction->gross_amount);
        $this->assertSame('52000.00', (string) $transaction->net_amount);
        $this->assertSame('1000.00', (string) $transaction->original_amount);
        $this->assertSame('52.00000000', (string) $transaction->exchange_rate);
        $this->assertSame($egp->id, $transaction->currency_id);
        $this->assertSame($usd->id, $transaction->original_currency_id);
    }

    public function test_create_transaction_attaches_documents(): void
    {
        Storage::fake('public');

        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Donation,
            direction: TransactionDirection::In,
            grossAmount: 100,
            feeAmount: 0,
            transactionDate: now()->toDateString(),
            referenceNumber: null,
            description: 'With receipt',
            notes: null,
            paymentMethodId: null,
            createdBy: $user->id,
            documents: [
                UploadedFile::fake()->image('receipt.jpg'),
            ],
        ));

        $this->assertCount(1, $transaction->getMedia('receipts'));
    }

    public function test_create_transaction_records_entry_with_running_balance(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Donation,
            direction: TransactionDirection::In,
            grossAmount: 750.00,
            feeAmount: 25.00,
            transactionDate: now()->toDateString(),
            referenceNumber: 'DON-100',
            description: 'Direct donation',
            notes: null,
            paymentMethodId: null,
            createdBy: $user->id,
        ));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'transaction_type' => TransactionType::Donation->value,
            'direction' => TransactionDirection::In->value,
            'net_amount' => '725.00',
            'created_by' => $user->id,
        ]);

        $expectedBalance = bcadd((string) $account->opening_balance, '725.00', 2);
        $this->assertSame($expectedBalance, (string) $transaction->fresh()->running_balance);
    }

    public function test_update_transaction_recalculates_running_balances(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $first = Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'gross_amount' => 100,
            'fee_amount' => 0,
            'net_amount' => 100,
            'running_balance' => bcadd((string) $account->opening_balance, '100.00', 2),
            'transaction_date' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
        ]);

        $second = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'gross_amount' => 50,
            'fee_amount' => 0,
            'net_amount' => 50,
            'running_balance' => bcadd((string) $account->opening_balance, '50.00', 2),
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->transactionService->updateTransaction($first, new UpdateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Donation,
            direction: TransactionDirection::In,
            grossAmount: 200.00,
            feeAmount: 0,
            transactionDate: $first->transaction_date->toDateString(),
            referenceNumber: $first->reference_number,
            description: $first->description,
            notes: $first->notes,
            paymentMethodId: $first->payment_method_id,
        ));

        $first->refresh();
        $second->refresh();

        $this->assertSame('200.00', (string) $first->net_amount);
        $this->assertSame(
            bcadd((string) $account->opening_balance, '150.00', 2),
            (string) $second->running_balance,
        );
    }

    public function test_general_expense_fx_posts_account_currency_amount(): void
    {
        $user = $this->createStaffUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $egp = Currency::query()->where('code', 'EGP')->firstOrFail();
        $account->update(['currency_id' => $egp->id]);

        $this->actingAs($user);

        $transaction = $this->transactionService->createForGeneralExpense([
            'account_id' => $account->id,
            'name' => 'Office rent',
            'amount' => 1000,
            'expense_date' => '2026-02-11',
            'original_currency_id' => $usd->id,
            'original_amount' => 1000,
            'exchange_rate' => 52,
        ]);

        $this->assertSame('52000.00', (string) $transaction->net_amount);
        $this->assertSame($egp->id, $transaction->currency_id);
        $this->assertSame('52000.00', (string) $transaction->generalExpense->amount);
    }
}
