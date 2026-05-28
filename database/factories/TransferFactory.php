<?php

namespace Database\Factories;

use App\Enums\TransferRecipientType;
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
        $recipientType = fake()->randomElement(TransferRecipientType::cases());

        return [
            'transaction_id'  => TransactionFactory::new()->transfer()->create()->id,
            'campaign_id'     => fake()->boolean(70)
                ? Campaign::inRandomOrder()->value('id')
                : null,
            'recipient_type'  => $recipientType,
            'recipient_name'  => match($recipientType) {
                TransferRecipientType::Vendor      => fake()->company(),
                TransferRecipientType::Beneficiary => fake()->name(),
                TransferRecipientType::User        => fake()->name(),
                TransferRecipientType::Other       => fake()->name(),
            },
            'recipient_phone' => fake()->optional(0.6)->phoneNumber(),
            'beneficiary_id'  => $recipientType === TransferRecipientType::Beneficiary
                ? Beneficiary::active()->inRandomOrder()->value('id')
                : null,
            'user_id'         => $recipientType === TransferRecipientType::User
                ? User::inRandomOrder()->value('id')
                : null,
            'amount'          => fake()->randomFloat(2, 50, 20_000),
            'transfer_date'   => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'purpose'         => fake()->sentence(5),
            'notes'           => fake()->optional(0.3)->sentence(),
            'created_by'      => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    public function toVendor(): static
    {
        return $this->state(fn () => [
            'recipient_type' => TransferRecipientType::Vendor,
            'beneficiary_id' => null,
            'user_id'        => null,
            'recipient_name' => fake()->company(),
        ]);
    }

    public function toBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state(fn () => [
            'recipient_type' => TransferRecipientType::Beneficiary,
            'beneficiary_id' => $beneficiary->id,
            'recipient_name' => $beneficiary->displayName,
            'user_id'        => null,
        ]);
    }
}
