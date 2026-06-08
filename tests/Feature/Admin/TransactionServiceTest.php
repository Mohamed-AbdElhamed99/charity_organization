<?php

namespace Tests\Feature\Admin;

use App\Contracts\Services\TransactionServiceInterface;
use App\Contracts\Services\TransferServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\CreateTransferDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Enums\TransferRecipientType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionServiceInterface $transactionService;

    private TransferServiceInterface $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->transactionService = app(TransactionServiceInterface::class);
        $this->transferService = app(TransferServiceInterface::class);
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
        $account = Account::query()->active()->orderBy('id')->firstOrFail();

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

        $reversal = $this->transactionService->reverseTransaction($original, $user->id);

        $this->assertDatabaseHas('transactions', [
            'id' => $reversal->id,
            'transaction_type' => TransactionType::Adjustment->value,
            'direction' => TransactionDirection::In->value,
            'account_id' => $account->id,
            'net_amount' => '500.00',
            'created_by' => $user->id,
        ]);

        $this->assertStringContainsString(
            "Reversal of transaction #{$original->id}",
            $reversal->description,
        );

        $expectedBalance = (string) $account->opening_balance;
        $this->assertSame($expectedBalance, (string) $reversal->fresh()->running_balance);
    }

    public function test_reverse_transaction_rejects_adjustment_entries(): void
    {
        $user = $this->createStaffUser();
        $account = Account::query()->active()->orderBy('id')->firstOrFail();

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

    public function test_transfer_creation_records_transaction_and_transfer(): void
    {
        $user = $this->createStaffUser();
        $account = Account::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transfer = $this->transferService->createTransfer(new CreateTransferDTO(
            recipientType: TransferRecipientType::Vendor,
            recipientName: 'ABC Supplies Co.',
            amount: 1250.50,
            transferDate: now()->toDateString(),
            purpose: 'Food boxes purchase',
            notes: 'Paid by cheque',
        ));

        $this->assertInstanceOf(Transfer::class, $transfer);
        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'recipient_type' => TransferRecipientType::Vendor->value,
            'recipient_name' => 'ABC Supplies Co.',
            'amount' => '1250.50',
            'purpose' => 'Food boxes purchase',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transfer->transaction_id,
            'account_id' => $account->id,
            'transaction_type' => TransactionType::Transfer->value,
            'direction' => TransactionDirection::Out->value,
            'net_amount' => '1250.50',
            'created_by' => $user->id,
        ]);

        $transaction = $transfer->transaction;
        $expectedBalance = bcsub((string) $account->opening_balance, '1250.50', 2);
        $this->assertSame($expectedBalance, (string) $transaction->running_balance);
    }

    public function test_transfer_uses_first_active_account_when_not_specified(): void
    {
        $user = $this->createStaffUser();
        $defaultAccount = Account::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transfer = $this->transferService->createTransfer(new CreateTransferDTO(
            recipientType: TransferRecipientType::Other,
            recipientName: 'Partner Organization',
            amount: 300.00,
            transferDate: now()->toDateString(),
            purpose: 'Emergency aid disbursement',
        ));

        $this->assertSame($defaultAccount->id, $transfer->transaction->account_id);
    }

    public function test_create_transaction_records_entry_with_running_balance(): void
    {
        $user = $this->createStaffUser();
        $account = Account::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user);

        $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: $account->id,
            transactionType: TransactionType::Donation,
            direction: TransactionDirection::In,
            currencyId: $account->currency_id,
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
        $account = Account::query()->active()->orderBy('id')->firstOrFail();

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
            currencyId: $account->currency_id,
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
}
