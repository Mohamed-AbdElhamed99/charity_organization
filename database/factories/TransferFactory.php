<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        $asUser = fake()->boolean();

        return [
            'transaction_id' => TransactionFactory::new()->transfer()->create()->id,
            'campaign_id' => fake()->boolean(70)
                ? Campaign::inRandomOrder()->value('id')
                : null,
            'recipient_type' => $asUser ? User::class : null,
            'recipient_id' => $asUser
                ? (User::inRandomOrder()->value('id') ?? User::factory())
                : null,
            'recipient_label' => $asUser ? null : fake()->company(),
            'recipient_phone' => fake()->optional(0.6)->phoneNumber(),
            'amount' => fake()->randomFloat(2, 50, 20_000),
            'transfer_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'purpose' => fake()->sentence(5),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    public function toUser(?User $user = null): static
    {
        return $this->state(fn () => [
            'recipient_type' => User::class,
            'recipient_id' => $user?->id ?? User::factory(),
            'recipient_label' => null,
        ]);
    }

    public function toBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state(fn () => [
            'recipient_type' => Beneficiary::class,
            'recipient_id' => $beneficiary->id,
            'recipient_label' => null,
        ]);
    }

    public function toLabel(string $label): static
    {
        return $this->state(fn () => [
            'recipient_type' => null,
            'recipient_id' => null,
            'recipient_label' => $label,
        ]);
    }
}
