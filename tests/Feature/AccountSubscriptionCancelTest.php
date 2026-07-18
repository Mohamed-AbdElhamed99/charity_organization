<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\DonationSubscriptionStatus;
use App\Models\DonationSubscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class AccountSubscriptionCancelTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_a_donor_can_cancel_their_own_subscription(): void
    {
        $donor = User::factory()->donor()->create();
        $subscription = DonationSubscription::factory()->create(['donor_id' => $donor->id]);

        $response = $this->actingAs($donor)->post(route('account.subscriptions.cancel', $subscription));

        $response->assertRedirect();
        $this->assertSame(DonationSubscriptionStatus::Canceled, $subscription->refresh()->status);
        $this->assertContains($subscription->stripe_subscription_id, $this->gateway->canceledSubscriptions);
    }

    public function test_a_donor_cannot_cancel_another_donors_subscription(): void
    {
        $owner = User::factory()->donor()->create();
        $intruder = User::factory()->donor()->create();
        $subscription = DonationSubscription::factory()->create(['donor_id' => $owner->id]);

        $response = $this->actingAs($intruder)->post(route('account.subscriptions.cancel', $subscription));

        $response->assertForbidden();
        $this->assertSame(DonationSubscriptionStatus::Active, $subscription->refresh()->status);
    }

    public function test_the_billing_portal_route_requires_ownership(): void
    {
        $owner = User::factory()->donor()->create();
        $intruder = User::factory()->donor()->create();
        $subscription = DonationSubscription::factory()->create(['donor_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('donations.subscriptions.portal', $subscription->stripe_subscription_id))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('donations.subscriptions.portal', $subscription->stripe_subscription_id))
            ->assertRedirect();
    }
}
