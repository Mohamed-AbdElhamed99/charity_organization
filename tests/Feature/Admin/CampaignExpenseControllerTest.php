<?php

namespace Tests\Feature\Admin;

use App\Models\BankAccount;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignExpenseControllerTest extends TestCase
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

    public function test_authorized_user_can_view_campaign_expenses_index(): void
    {
        $user = $this->createAuthorizedUser();
        CampaignExpense::factory()->count(2)->create(['responsible_user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.campaign-expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaign-expenses/campaign-expenses-index')
                ->has('expenses.data', 2)
            );
    }

    public function test_authorized_user_can_create_campaign_expense(): void
    {
        $user = $this->createAuthorizedUser();
        $campaign = Campaign::factory()->create(['created_by' => $user->id]);
        $item = Item::factory()->create();
        $account = BankAccount::query()->active()->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.campaign-expenses.store'), [
                'campaign_id' => $campaign->id,
                'account_id' => $account->id,
                'item_id' => $item->id,
                'item_price' => 50,
                'quantity' => 10,
                'expense_date' => now()->format('Y-m-d'),
                'responsible_user_id' => $user->id,
                'description' => 'Food boxes purchase',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('campaign_expenses', [
            'campaign_id' => $campaign->id,
            'item_id' => $item->id,
            'amount' => 500,
        ]);

        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'campaign_expense',
            'direction' => 'out',
            'net_amount' => 500,
        ]);
    }
}
