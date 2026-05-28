<?php

namespace Database\Factories;

use App\Enums\StripeStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        $isGeneral = fake()->boolean(30);
        $hasStripe = fake()->boolean(40);

        return [
            'transaction_id'          => TransactionFactory::new()->donation()->create()->id,
            'donor_id'                => User::role('donor')->inRandomOrder()->value('id'),
            'campaign_id'             => $isGeneral ? null : Campaign::active()->inRandomOrder()->value('id'),
            'is_general'              => $isGeneral,
            'purpose_note'            => fake()->optional(0.4)->sentence(),
            'stripe_payment_intent_id' => $hasStripe ? 'pi_' . Str::random(24) : null,
            'stripe_charge_id'        => $hasStripe ? 'ch_' . Str::random(24) : null,
            'stripe_status'           => $hasStripe ? StripeStatus::Succeeded : null,
            'donor_covers_fee'        => fake()->boolean(20),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function general(): static
    {
        return $this->state(fn () => [
            'is_general'  => true,
            'campaign_id' => null,
        ]);
    }

    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => [
            'is_general'  => false,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function viaStripe(): static
    {
        return $this->state(fn () => [
            'stripe_payment_intent_id' => 'pi_' . Str::random(24),
            'stripe_charge_id'         => 'ch_' . Str::random(24),
            'stripe_status'            => StripeStatus::Succeeded,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn () => ['donor_id' => null]);
    }
}
