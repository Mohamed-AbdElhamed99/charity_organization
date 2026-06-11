<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GeneralExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_authorized_user_can_view_general_expenses_index(): void
    {
        $user = $this->createAuthorizedUser();
        GeneralExpense::factory()->count(2)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.general-expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/general-expenses/general-expenses-index')
                ->has('expenses.data', 2)
            );
    }

    public function test_authorized_user_can_create_general_expense_with_ledger_entry(): void
    {
        $user = $this->createAuthorizedUser();
        $account = Account::query()->active()->firstOrFail();
        $category = GeneralExpenseCategory::factory()->create(['is_active' => true]);
        $paymentMethod = PaymentMethod::query()->active()->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.general-expenses.store'), [
                'account_id' => $account->id,
                'name' => 'Zoom Pro Monthly',
                'amount' => 149.99,
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'vendor_name' => 'Zoom',
                'is_recurring' => true,
                'description' => 'Zoom subscription',
                'notes' => 'Monthly billing',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'general_expense',
            'direction' => 'out',
            'net_amount' => 149.99,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $this->assertDatabaseHas('general_expenses', [
            'name' => 'Zoom Pro Monthly',
            'amount' => 149.99,
            'category_id' => $category->id,
        ]);
    }

    public function test_authorized_user_can_update_general_expense_metadata_only(): void
    {
        $user = $this->createAuthorizedUser();
        $expense = GeneralExpense::factory()->create(['created_by' => $user->id]);
        $originalAmount = $expense->amount;
        $category = GeneralExpenseCategory::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->patch(route('admin.general-expenses.update', $expense), [
                'category_id' => $category->id,
                'name' => 'Updated Expense Name',
                'vendor_name' => 'Updated Vendor',
                'is_recurring' => false,
                'notes' => 'Updated notes',
            ])
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('Updated Expense Name', $expense->name);
        $this->assertSame((float) $originalAmount, (float) $expense->amount);
    }

    public function test_destroy_reverses_linked_transaction(): void
    {
        $user = $this->createAuthorizedUser();
        $expense = GeneralExpense::factory()->create(['created_by' => $user->id]);
        $transaction = $expense->transaction;

        $this->actingAs($user)
            ->delete(route('admin.general-expenses.destroy', $expense))
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'adjustment',
            'description' => "Reversal of transaction #{$transaction->id}",
        ]);
    }
}
