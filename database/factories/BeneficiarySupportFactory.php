<?php

namespace Database\Factories;

use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiarySupport>
 */
class BeneficiarySupportFactory extends Factory
{
    protected $model = BeneficiarySupport::class;

    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory()->individual()->active(),
            'campaign_id' => Campaign::factory()->active(),
            'supported_at' => fake()->date(),
            'status' => fake()->randomElement(SupportStatus::cases()),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
