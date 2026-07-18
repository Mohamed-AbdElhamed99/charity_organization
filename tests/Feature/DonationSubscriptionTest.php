<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\DonationSubscriptionStatus;
use App\Enums\RecurrenceFrequency;
use App\Models\Campaign;
use App\Models\DonationSubscription;
use App\Models\DonorProfile;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class DonationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function createDonatableCampaign(): Campaign
    {
        return Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
        ]);
    }

    public function test_subscribe_validation_rejects_both_campaign_and_general_within_an_allocation(): void
    {
        $campaign = $this->createDonatableCampaign();

        $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['campaign_id' => $campaign->id, 'is_general' => true, 'amount' => 5000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-sub@example.com',
        ])->assertUnprocessable();
    }

    public function test_subscribe_rejects_invalid_frequency(): void
    {
        $this->postJson(route('donations.subscribe'), [
            'frequency' => 'daily',
            'allocations' => [
                ['is_general' => true, 'amount' => 5000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-bad-freq@example.com',
        ])->assertUnprocessable();
    }

    public function test_subscribe_creates_active_subscription_and_stripe_customer(): void
    {
        $campaign = $this->createDonatableCampaign();

        $response = $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['campaign_id' => $campaign->id, 'is_general' => false, 'amount' => 10000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-monthly@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('amount', 10000)
            ->assertJsonPath('frequency', 'monthly')
            ->assertJsonStructure(['clientSecret', 'paymentIntentId', 'subscriptionId', 'donationSubscriptionId']);

        $subscription = DonationSubscription::query()->with('allocations')->findOrFail($response->json('donationSubscriptionId'));
        $this->assertSame(DonationSubscriptionStatus::Active, $subscription->status);
        $this->assertSame(RecurrenceFrequency::Monthly, $subscription->frequency);
        $this->assertSame(10000, $subscription->amount_cents);
        $this->assertCount(1, $subscription->allocations);
        $this->assertSame($campaign->id, $subscription->allocations->first()->campaign_id);
        $this->assertFalse($subscription->allocations->first()->is_general);

        $profile = DonorProfile::query()->where('user_id', $subscription->donor_id)->firstOrFail();
        $this->assertNotNull($profile->stripe_customer_id);
        $this->assertSame($subscription->stripe_customer_id, $profile->stripe_customer_id);
    }

    /**
     * @return array<string, array{0: string, 1: array{interval: string, interval_count: int}}>
     */
    public static function frequencyProvider(): array
    {
        return [
            'weekly' => ['weekly', ['interval' => 'week', 'interval_count' => 1]],
            'monthly' => ['monthly', ['interval' => 'month', 'interval_count' => 1]],
            'quarterly' => ['quarterly', ['interval' => 'month', 'interval_count' => 3]],
            'yearly' => ['yearly', ['interval' => 'year', 'interval_count' => 1]],
        ];
    }

    /**
     * @param  array{interval: string, interval_count: int}  $expectedRecurring
     */
    #[DataProvider('frequencyProvider')]
    public function test_subscribe_maps_each_frequency_to_the_correct_stripe_interval(string $frequency, array $expectedRecurring): void
    {
        $response = $this->postJson(route('donations.subscribe'), [
            'frequency' => $frequency,
            'allocations' => [
                ['is_general' => true, 'amount' => 5000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => "jane-{$frequency}@example.com",
        ]);

        $response->assertOk()->assertJsonPath('frequency', $frequency);

        $this->assertCount(1, $this->gateway->subscriptionCalls);
        $call = $this->gateway->subscriptionCalls[0];
        $this->assertSame(RecurrenceFrequency::from($frequency), $call['frequency']);
        $this->assertSame($expectedRecurring, $call['frequency']->toStripeRecurring());
    }

    public function test_subscribe_splits_one_subscription_across_multiple_campaigns(): void
    {
        $campaignOne = $this->createDonatableCampaign();
        $campaignTwo = $this->createDonatableCampaign();

        $response = $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['campaign_id' => $campaignOne->id, 'is_general' => false, 'amount' => 3000],
                ['campaign_id' => $campaignTwo->id, 'is_general' => false, 'amount' => 4000],
                ['is_general' => true, 'amount' => 3000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-split@example.com',
        ]);

        $response->assertOk()->assertJsonPath('amount', 10000);

        $subscription = DonationSubscription::query()->with('allocations')->findOrFail($response->json('donationSubscriptionId'));
        $this->assertSame(10000, $subscription->amount_cents);
        $this->assertCount(3, $subscription->allocations);
        $this->assertSame(10000, (int) $subscription->allocations->sum('amount_cents'));

        $this->assertTrue($subscription->allocations->contains(fn ($a) => $a->campaign_id === $campaignOne->id && $a->amount_cents === 3000));
        $this->assertTrue($subscription->allocations->contains(fn ($a) => $a->campaign_id === $campaignTwo->id && $a->amount_cents === 4000));
        $this->assertTrue($subscription->allocations->contains(fn ($a) => $a->is_general && $a->amount_cents === 3000));
    }

    public function test_subscribe_falls_back_to_general_when_an_allocations_campaign_is_unavailable(): void
    {
        $response = $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['campaign_id' => 999999, 'is_general' => false, 'amount' => 5000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-fallback-sub@example.com',
        ]);

        $response->assertOk();

        $subscription = DonationSubscription::query()->with('allocations')->findOrFail($response->json('donationSubscriptionId'));
        $allocation = $subscription->allocations->first();
        $this->assertTrue($allocation->is_general);
        $this->assertNull($allocation->campaign_id);
    }

    public function test_subscribe_reuses_existing_stripe_customer_for_returning_donor(): void
    {
        $first = $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['is_general' => true, 'amount' => 5000],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Repeat',
            'last_name' => 'Donor',
            'email' => 'repeat-donor@example.com',
        ]);
        $first->assertOk();

        $second = $this->postJson(route('donations.subscribe'), [
            'frequency' => 'monthly',
            'allocations' => [
                ['is_general' => true, 'amount' => 7500],
            ],
            'donor_covers_fee' => false,
            'first_name' => 'Repeat',
            'last_name' => 'Donor',
            'email' => 'repeat-donor@example.com',
        ]);
        $second->assertOk();

        $firstSubscription = DonationSubscription::query()->findOrFail($first->json('donationSubscriptionId'));
        $secondSubscription = DonationSubscription::query()->findOrFail($second->json('donationSubscriptionId'));

        $this->assertSame($firstSubscription->stripe_customer_id, $secondSubscription->stripe_customer_id);
    }
}
