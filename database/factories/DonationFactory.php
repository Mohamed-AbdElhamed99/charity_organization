<?php

namespace Database\Factories;

use App\Enums\DonationStatus;
use App\Enums\StripeStatus;
use App\Models\Campaign;
use App\Models\Donation;
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
        $amountCents = fake()->numberBetween(1000, 50000);

        return [
            'transaction_id' => null,
            'donor_id' => User::role('donor')->inRandomOrder()->value('id'),
            'campaign_id' => $isGeneral ? null : Campaign::active()->inRandomOrder()->value('id'),
            'is_general' => $isGeneral,
            'amount' => $amountCents,
            'status' => DonationStatus::Succeeded,
            'purpose_note' => fake()->optional(0.4)->sentence(),
            'stripe_payment_intent_id' => $hasStripe ? 'pi_'.Str::random(24) : null,
            'stripe_charge_id' => $hasStripe ? 'ch_'.Str::random(24) : null,
            'stripe_status' => $hasStripe ? StripeStatus::Succeeded : null,
            'donor_covers_fee' => fake()->boolean(20),
            'is_anonymous' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Donation $donation) {
            if ($donation->transaction_id === null && $donation->status === DonationStatus::Succeeded) {
                $transaction = TransactionFactory::new()->donation()->create([
                    'gross_amount' => $donation->amount / 100,
                    'net_amount' => ($donation->amount / 100) * 0.97,
                    'fee_amount' => ($donation->amount / 100) * 0.03,
                ]);
                $donation->update(['transaction_id' => $transaction->id]);
            }
        });
    }

    public function general(): static
    {
        return $this->state(fn () => [
            'is_general' => true,
            'campaign_id' => null,
        ]);
    }

    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => [
            'is_general' => false,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'transaction_id' => null,
            'status' => DonationStatus::Pending,
            'stripe_status' => StripeStatus::Pending,
            'stripe_payment_intent_id' => 'pi_'.Str::random(24),
            'stripe_charge_id' => null,
        ]);
    }

    public function viaStripe(): static
    {
        return $this->state(fn () => [
            'stripe_payment_intent_id' => 'pi_'.Str::random(24),
            'stripe_charge_id' => 'ch_'.Str::random(24),
            'stripe_status' => StripeStatus::Succeeded,
            'status' => DonationStatus::Succeeded,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn () => [
            'donor_id' => null,
            'is_anonymous' => true,
        ]);
    }
}
