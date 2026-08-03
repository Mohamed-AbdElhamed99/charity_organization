<?php

namespace Tests\Feature\Admin;

use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
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

    public function test_guests_cannot_access_currencies_index(): void
    {
        $this->get(route('admin.currencies.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_currencies_index(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->get(route('admin.currencies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/currencies/currencies-index')
                ->has('currencies.data')
            );
    }

    public function test_authorized_user_can_create_currency(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.currencies.store'), [
                'code' => 'jpy',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'is_active' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', [
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    public function test_creating_currency_flashes_the_created_record(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->from(route('admin.currencies.index'))
            ->post(route('admin.currencies.store'), [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'is_active' => true,
                'is_default' => false,
            ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('currency');

        $currency = Currency::query()->where('code', 'AUD')->firstOrFail();
        $flash = session('inertia.flash_data');

        $this->assertIsArray($flash);
        $this->assertSame($currency->id, $flash['currency']['id']);
        $this->assertSame('AUD', $flash['currency']['code']);
        $this->assertSame('Australian Dollar', $flash['currency']['name']);
    }

    public function test_setting_default_currency_clears_previous_default(): void
    {
        $user = $this->createAuthorizedUser();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $egp = Currency::query()->where('code', 'EGP')->firstOrFail();

        $usd->update(['is_default' => true]);
        $egp->update(['is_default' => false]);

        $this->actingAs($user)
            ->patch(route('admin.currencies.update', $egp), [
                'code' => $egp->code,
                'name' => $egp->name,
                'symbol' => $egp->symbol,
                'is_active' => true,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($egp->fresh()->is_default);
        $this->assertFalse($usd->fresh()->is_default);
    }

    public function test_unreferenced_currency_can_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $currency = Currency::factory()->create([
            'code' => 'NZD',
            'name' => 'New Zealand Dollar',
            'symbol' => 'NZ$',
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.currencies.destroy', $currency))
            ->assertRedirect();

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_referenced_currency_cannot_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $account = BankAccount::query()->active()->orderBy('id')->firstOrFail();
        $currency = Currency::query()->findOrFail($account->currency_id);

        $this->actingAs($user)
            ->delete(route('admin.currencies.destroy', $currency))
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }

    public function test_currency_referenced_by_transaction_cannot_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $currency = Currency::factory()->create([
            'code' => 'CHF',
            'name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'is_default' => false,
        ]);

        Transaction::factory()->create([
            'currency_id' => $currency->id,
            'original_currency_id' => $currency->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.currencies.destroy', $currency))
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }
}
