<?php

namespace Database\Factories;

use App\Enums\DonationSubscriptionStatus;
use App\Enums\RecurrenceFrequency;
use App\Models\Campaign;
use App\Models\DonationSubscription;
use App\Models\DonationSubscriptionAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DonationSubscription>
 */
class DonationSubscriptionFactory extends Factory
{
    protected $model = DonationSubscription::class;

    public function definition(): array
    {
        return [
            'donor_id' => User::factory(),
            'amount_cents' => fake()->numberBetween(1000, 20000),
            'donor_covers_fee' => false,
            'frequency' => RecurrenceFrequency::Monthly,
            'stripe_customer_id' => 'cus_'.Str::random(14),
            'stripe_subscription_id' => 'sub_'.Str::random(14),
            'billing_cycle_anchor_at' => now(),
            'status' => DonationSubscriptionStatus::Active,
            'metadata' => [],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (DonationSubscription $subscription) {
            if ($subscription->allocations()->doesntExist()) {
                DonationSubscriptionAllocation::factory()->create([
                    'donation_subscription_id' => $subscription->id,
                    'is_general' => true,
                    'campaign_id' => null,
                    'amount_cents' => $subscription->amount_cents,
                ]);
            }
        });
    }

    /**
     * Replaces the default general allocation with a single allocation for
     * the given campaign, covering the subscription's full amount.
     */
    public function forCampaign(Campaign $campaign): static
    {
        return $this->afterCreating(function (DonationSubscription $subscription) use ($campaign) {
            $subscription->allocations()->delete();

            DonationSubscriptionAllocation::factory()->create([
                'donation_subscription_id' => $subscription->id,
                'is_general' => false,
                'campaign_id' => $campaign->id,
                'amount_cents' => $subscription->amount_cents,
            ]);
        });
    }
}
