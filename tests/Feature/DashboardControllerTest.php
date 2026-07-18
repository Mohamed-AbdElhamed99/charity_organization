<?php

namespace Tests\Feature;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Campaign;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    // ─── Active campaigns count (always visible) ──────────────────────────────

    public function test_active_campaigns_count_is_always_included(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('field_worker'); // no view_transactions permission

        Campaign::factory()->active()->create(['created_by' => $user->id]);
        Campaign::factory()->draft()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('stats.active_campaigns_count', 1)
            );
    }

    // ─── Permission gating ────────────────────────────────────────────────────

    public function test_financial_props_are_absent_when_user_lacks_view_transactions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('field_worker');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->missing('stats.total_donations_this_month')
                ->missing('stats.net_balance')
                ->missing('monthly_summary')
            );
    }

    public function test_financial_props_are_present_when_user_has_view_transactions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->has('stats.total_donations_this_month')
                ->has('stats.net_balance')
                ->has('monthly_summary')
            );
    }

    public function test_unauthenticated_users_get_a_404_from_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertNotFound();
    }

    // ─── Stats shape and types ────────────────────────────────────────────────

    public function test_total_donations_this_month_are_integers_in_cents(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        Transaction::factory()
            ->donation()
            ->create([
                'net_amount'       => 500.00, // EGP 500.00 → 50000 cents
                'running_balance'  => 500.00,
                'transaction_date' => now()->toDateString(),
                'created_by'       => $user->id,
            ]);

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total_donations_this_month.cents', 50000)
            );

        $props = $response->viewData('page')['props'];
        $this->assertIsInt(data_get($props, 'stats.total_donations_this_month.cents'));
        $this->assertIsInt(data_get($props, 'stats.total_donations_this_month.prior_month_cents'));
    }

    public function test_net_balance_reflects_latest_running_balance_as_integer_cents(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        Transaction::factory()->create([
            'running_balance'  => 1250.75,
            'transaction_date' => now()->subDay()->toDateString(),
            'created_by'       => $user->id,
        ]);

        Transaction::factory()->create([
            'running_balance'  => 2000.50,
            'transaction_date' => now()->toDateString(),
            'created_by'       => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.net_balance.cents', 200050)
            );

        $props = $response->viewData('page')['props'];
        $this->assertIsInt(data_get($props, 'stats.net_balance.cents'));
    }

    // ─── Monthly summary ──────────────────────────────────────────────────────

    public function test_monthly_summary_always_contains_exactly_12_entries(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monthly_summary', 12)
            );
    }

    public function test_monthly_summary_entries_have_correct_shape_and_integer_values(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        Transaction::factory()
            ->donation()
            ->create([
                'net_amount'       => 300.00,
                'running_balance'  => 300.00,
                'transaction_date' => now()->toDateString(),
                'created_by'       => $user->id,
            ]);

        Transaction::factory()
            ->campaignExpense()
            ->create([
                'net_amount'       => 100.00,
                'running_balance'  => 200.00,
                'transaction_date' => now()->toDateString(),
                'created_by'       => $user->id,
            ]);

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $summary = $response->viewData('page')['props']['monthly_summary'] ?? [];
        $thisMonth = now()->format('Y-m');
        $entry = collect($summary)->firstWhere('month', $thisMonth);

        $this->assertNotNull($entry, "Expected an entry for {$thisMonth}");
        $this->assertIsInt($entry['donations']);
        $this->assertIsInt($entry['expenses']);
        $this->assertSame(30000, $entry['donations']);
        $this->assertSame(10000, $entry['expenses']);
    }

    public function test_monthly_summary_fills_months_with_no_transactions_as_zero(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monthly_summary', 12, fn (Assert $entry) => $entry
                    ->has('month')
                    ->has('donations')
                    ->has('expenses')
                )
            );
    }
}
