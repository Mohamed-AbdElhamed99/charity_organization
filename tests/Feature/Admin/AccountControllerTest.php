<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $currency = Currency::factory()->create();

        return array_merge([
            'name' => 'Main Operations Account',
            'account_number' => '1234-5678-9012-3456',
            'bank_name' => 'Cairo Bank',
            'bank_branch' => 'Nasr City',
            'currency_id' => $currency->id,
            'type' => AccountType::Bank->value,
            'opening_balance' => 10000.00,
            'is_active' => true,
            'notes' => 'Primary account for all transactions.',
        ], $overrides);
    }

    public function test_guests_cannot_access_accounts_index(): void
    {
        $this->get(route('admin.accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_accounts_index(): void
    {
        $user = $this->createAuthorizedUser();
        Currency::factory()->create();
        Account::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/accounts/accounts-index')
                ->has('accounts.data', 3)
                ->has('currencies')
                ->has('accountTypes')
            );
    }

    public function test_accounts_index_honors_search(): void
    {
        $user = $this->createAuthorizedUser();
        Currency::factory()->create();
        Account::factory()->create(['name' => 'Special Reserve Fund']);
        Account::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.accounts.index', ['query' => 'Special']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('accounts.total', 1)
                ->where('search.query', 'Special')
            );
    }

    public function test_accounts_index_filters_by_type(): void
    {
        $user = $this->createAuthorizedUser();
        Currency::factory()->create();
        Account::factory()->bank()->count(2)->create();
        Account::factory()->cash()->count(1)->create();

        $this->actingAs($user)
            ->get(route('admin.accounts.index', ['type' => 'cash']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('accounts.total', 1)
            );
    }

    public function test_authorized_user_can_create_account(): void
    {
        $user = $this->createAuthorizedUser();
        $payload = $this->validPayload();

        $this->actingAs($user)
            ->post(route('admin.accounts.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'name' => 'Main Operations Account',
            'bank_name' => 'Cairo Bank',
            'type' => AccountType::Bank->value,
            'is_active' => true,
        ]);
    }

    public function test_user_without_permission_cannot_create_account(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();

        $this->actingAs($user)
            ->post(route('admin.accounts.store'), $payload)
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.accounts.store'), [])
            ->assertSessionHasErrors(['name', 'currency_id', 'type', 'opening_balance', 'is_active']);
    }

    public function test_authorized_user_can_update_account(): void
    {
        $user = $this->createAuthorizedUser();
        $currency = Currency::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id]);

        $this->actingAs($user)
            ->patch(route('admin.accounts.update', $account), $this->validPayload([
                'currency_id' => $currency->id,
                'name' => 'Updated Account Name',
            ]))
            ->assertRedirect();

        $this->assertSame('Updated Account Name', $account->fresh()->name);
    }

    public function test_user_without_permission_cannot_update_account(): void
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id]);

        $this->actingAs($user)
            ->patch(route('admin.accounts.update', $account), $this->validPayload([
                'currency_id' => $currency->id,
            ]))
            ->assertForbidden();
    }

    public function test_authorized_user_can_soft_delete_account(): void
    {
        $user = $this->createAuthorizedUser();
        $currency = Currency::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id]);

        $this->actingAs($user)
            ->delete(route('admin.accounts.destroy', $account))
            ->assertRedirect();

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_authorized_user_can_restore_account(): void
    {
        $user = $this->createAuthorizedUser();
        $currency = Currency::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id]);
        $account->delete();

        $this->actingAs($user)
            ->post(route('admin.accounts.restore', $account->id))
            ->assertRedirect();

        $this->assertNull($account->fresh()->deleted_at);
    }

    public function test_authorized_user_can_bulk_delete_accounts(): void
    {
        $user = $this->createAuthorizedUser();
        Currency::factory()->create();
        $accounts = Account::factory()->count(3)->create();

        $this->actingAs($user)
            ->post(route('admin.accounts.bulk-destroy'), [
                'ids' => $accounts->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($accounts as $account) {
            $this->assertSoftDeleted('accounts', ['id' => $account->id]);
        }
    }

    public function test_user_without_permission_cannot_bulk_delete_accounts(): void
    {
        $user = User::factory()->create();
        Currency::factory()->create();
        $accounts = Account::factory()->count(2)->create();

        $this->actingAs($user)
            ->post(route('admin.accounts.bulk-destroy'), [
                'ids' => $accounts->pluck('id')->all(),
            ])
            ->assertForbidden();
    }
}
