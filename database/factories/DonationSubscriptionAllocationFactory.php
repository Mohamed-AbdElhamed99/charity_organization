<?php

namespace Database\Factories;

use App\Models\DonationSubscription;
use App\Models\DonationSubscriptionAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationSubscriptionAllocation>
 */
class DonationSubscriptionAllocationFactory extends Factory
{
    protected $model = DonationSubscriptionAllocation::class;

    public function definition(): array
    {
        return [
            'donation_subscription_id' => DonationSubscription::factory(),
            'campaign_id' => null,
            'is_general' => true,
            'amount_cents' => fake()->numberBetween(1000, 20000),
        ];
    }
}
