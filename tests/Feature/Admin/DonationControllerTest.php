<?php

namespace Tests\Feature\Admin;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DonationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_authorized_user_can_view_donations_index(): void
    {
        $user = $this->createSuperAdmin();
        $this->seed(FinancialFoundationSeeder::class);

        Donation::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.donations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/donations/donations-index')
                ->has('donations.data', 2)
                ->has('summary')
            );
    }

    public function test_filters_narrow_donation_results(): void
    {
        $user = $this->createSuperAdmin();
        $this->seed(FinancialFoundationSeeder::class);

        Donation::factory()->create([
            'status' => DonationStatus::Succeeded,
            'amount' => 10000,
        ]);
        Donation::factory()->pending()->create(['amount' => 5000]);

        $this->actingAs($user)
            ->get(route('admin.donations.index', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('donations.data', 1)
            );
    }

    public function test_export_uses_same_filters_as_index(): void
    {
        $user = $this->createSuperAdmin();
        $this->seed(FinancialFoundationSeeder::class);

        Donation::factory()->create(['status' => DonationStatus::Succeeded]);
        Donation::factory()->pending()->create();

        $this->actingAs($user)
            ->get(route('admin.donations.export', ['status' => 'succeeded', 'format' => 'csv']))
            ->assertOk();
    }
}
