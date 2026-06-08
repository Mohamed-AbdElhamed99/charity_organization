<?php

namespace Tests\Feature\Admin;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function createStaffUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        return $user;
    }

    private function seedFinancialData(): void
    {
        $this->seed(FinancialFoundationSeeder::class);
    }

    public function test_guests_cannot_access_transactions_index(): void
    {
        $this->get(route('admin.transactions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_transactions_index(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        Transaction::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-index')
                ->has('transactions.data', 2)
                ->has('accounts')
                ->has('currencies')
                ->has('paymentMethods')
            );
    }

    public function test_authorized_user_can_view_transaction_show_page(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $transaction = Transaction::factory()->donation()->create();

        $this->actingAs($user)
            ->get(route('admin.transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transactions/transactions-show')
                ->where('transaction.id', $transaction->id)
            );
    }

    public function test_authorized_user_can_create_transaction(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = Account::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->active()->orderBy('id')->firstOrFail();
        $paymentMethod = PaymentMethod::query()->active()->orderBy('id')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type' => TransactionType::Donation->value,
                'direction' => TransactionDirection::In->value,
                'currency_id' => $currency->id,
                'gross_amount' => 1000,
                'fee_amount' => 50,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'REF-001',
                'description' => 'Manual donation entry',
                'notes' => 'Test note',
                'payment_method_id' => $paymentMethod->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'transaction_type' => TransactionType::Donation->value,
            'direction' => TransactionDirection::In->value,
            'gross_amount' => '1000.00',
            'fee_amount' => '50.00',
            'net_amount' => '950.00',
            'reference_number' => 'REF-001',
            'description' => 'Manual donation entry',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_update_transaction(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = Account::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->active()->orderBy('id')->firstOrFail();

        $transaction = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'gross_amount' => 200,
            'fee_amount' => 0,
            'net_amount' => 200,
            'running_balance' => bcsub((string) $account->opening_balance, '200.00', 2),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.transactions.update', $transaction), [
                'account_id' => $account->id,
                'transaction_type' => TransactionType::Transfer->value,
                'direction' => TransactionDirection::Out->value,
                'currency_id' => $currency->id,
                'gross_amount' => 300,
                'fee_amount' => 10,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'REF-UPDATED',
                'description' => 'Updated transfer',
                'notes' => 'Updated notes',
                'payment_method_id' => null,
            ])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('300.00', (string) $transaction->gross_amount);
        $this->assertSame('10.00', (string) $transaction->fee_amount);
        $this->assertSame('290.00', (string) $transaction->net_amount);
        $this->assertSame('REF-UPDATED', $transaction->reference_number);
        $this->assertSame('Updated transfer', $transaction->description);
    }

    public function test_authorized_user_can_export_filtered_transactions_as_csv(): void
    {
        $user = $this->createSuperAdmin();
        $this->seedFinancialData();

        $account = Account::query()->active()->orderBy('id')->firstOrFail();

        Transaction::factory()->donation()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-01-15',
            'description' => 'January donation',
            'net_amount' => 500,
            'gross_amount' => 500,
            'fee_amount' => 0,
            'running_balance' => bcadd((string) $account->opening_balance, '500.00', 2),
        ]);

        Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $account->currency_id,
            'transaction_date' => '2026-02-10',
            'description' => 'February transfer',
            'net_amount' => 100,
            'gross_amount' => 100,
            'fee_amount' => 0,
            'running_balance' => bcadd((string) $account->opening_balance, '400.00', 2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.transactions.export', [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Date,Balance,Expenses,Donations,Transfer,Details', $content);
        $this->assertStringContainsString('January donation', $content);
        $this->assertStringNotContainsString('February transfer', $content);
    }

    public function test_staff_without_edit_permission_cannot_update_transaction(): void
    {
        $user = $this->createStaffUser();
        $this->seedFinancialData();

        $account = Account::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->active()->orderBy('id')->firstOrFail();

        $transaction = Transaction::factory()->transfer()->create([
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('admin.transactions.update', $transaction), [
                'account_id' => $account->id,
                'transaction_type' => TransactionType::Transfer->value,
                'direction' => TransactionDirection::Out->value,
                'currency_id' => $currency->id,
                'gross_amount' => 300,
                'fee_amount' => 0,
                'transaction_date' => now()->toDateString(),
                'description' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_user_without_view_permission_cannot_export_transactions(): void
    {
        $user = User::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($user)
            ->get(route('admin.transactions.export'))
            ->assertForbidden();
    }
}
