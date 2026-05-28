<?php

namespace Database\Factories;

use App\Models\BeneficiaryFamily;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryFamily>
 */
class BeneficiaryFamilyFactory extends Factory
{
    protected $model = BeneficiaryFamily::class;

    public function definition(): array
    {
        $country = Country::inRandomOrder()->first();
        $state   = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        $totalMembers = fake()->numberBetween(2, 10);

        return [
            'beneficiary_id'   => \App\Models\Beneficiary::factory(),
            'household_name'   => fake()->lastName() . ' Family',
            'national_id'      => fake()->optional(0.9)->numerify('##############'),
            'phone'            => fake()->optional(0.8)->phoneNumber(),
            'address'          => fake()->optional(0.8)->streetAddress(),
            'village'          => fake()->optional(0.4)->city(),
            'country_id'       => $country?->id,
            'state_id'         => $state?->id,
            'social_status'    => fake()->optional(0.9)->randomElement([
                'Widowed', 'Divorced', 'Single Parent', 'Married', 'Separated',
            ]),
            'total_members'    => $totalMembers,
            'monthly_income'   => fake()->optional(0.7)->randomFloat(2, 0, 3_000),
            'housing_type'     => fake()->optional(0.8)->randomElement([
                'Apartment', 'House', 'Rented Room', 'Informal Shelter', 'Shared Housing',
            ]),
            'housing_ownership' => fake()->optional(0.8)->randomElement([
                'Owned', 'Rented', 'Family-Owned', 'Informal', 'Government-Provided',
            ]),
            'monthly_rent'     => fake()->optional(0.5)->randomFloat(2, 50, 800),
            'notes'            => fake()->optional(0.2)->sentence(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    /** Create family with members using afterCreating */
    public function withMembers(int $count = 3): static
    {
        return $this->afterCreating(function (BeneficiaryFamily $family) use ($count) {
            BeneficiaryFamilyMemberFactory::new()
                ->count($count)
                ->for($family, 'family')
                ->create();
        });
    }
}
