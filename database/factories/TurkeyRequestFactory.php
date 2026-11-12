<?php

namespace Database\Factories;

use App\Enums\TurkeyRequestStatus;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\TurkeyRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TurkeyRequest>
 */
class TurkeyRequestFactory extends Factory
{
    protected $model = TurkeyRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory()->individual()->pending(),
            'campaign_id' => Campaign::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('##########'),
            'address' => fake()->address(),
            'quantity' => 1,
            'code' => 'TRK-'.strtoupper(fake()->unique()->bothify('??????')),
            'status' => TurkeyRequestStatus::Pending,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => TurkeyRequestStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => TurkeyRequestStatus::Cancelled]);
    }
}
