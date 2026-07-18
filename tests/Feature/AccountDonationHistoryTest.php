<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\DonationSubscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDonationHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_sees_only_their_own_donations_and_subscriptions(): void
    {
        $donor = User::factory()->donor()->create();
        $otherDonor = User::factory()->donor()->create();

        Donation::factory()->create(['donor_id' => $donor->id, 'is_general' => true]);
        Donation::factory()->create(['donor_id' => $otherDonor->id, 'is_general' => true]);

        DonationSubscription::factory()->create(['donor_id' => $donor->id]);
        DonationSubscription::factory()->create(['donor_id' => $otherDonor->id]);

        $response = $this->actingAs($donor)->get(route('account.donations.index'));

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->has('donations.data', 1);
            $page->has('subscriptions', 1);
        });
    }
}
