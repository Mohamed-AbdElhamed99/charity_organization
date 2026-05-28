<?php

namespace Database\Factories;

use App\Models\BeneficiaryOrganization;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryOrganization>
 */
class BeneficiaryOrganizationFactory extends Factory
{
    protected $model = BeneficiaryOrganization::class;

    public function definition(): array
    {
        $country = Country::inRandomOrder()->first();
        $state   = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        return [
            'beneficiary_id'    => \App\Models\Beneficiary::factory(),
            'name'              => fake()->company(),
            'organization_type' => fake()->optional(0.9)->randomElement([
                'Hospital', 'Clinic', 'School', 'Orphanage',
                'Elderly Care Home', 'Community Center', 'Mosque', 'Church', 'NGO',
            ]),
            'charity_number'    => fake()->optional(0.5)->numerify('CHR-######'),
            'phone'             => fake()->optional(0.8)->phoneNumber(),
            'email'             => fake()->optional(0.6)->companyEmail(),
            'address'           => fake()->optional(0.8)->address(),
            'country_id'        => $country?->id,
            'state_id'          => $state?->id,
            'contact_person'    => fake()->optional(0.7)->name(),
            'contact_phone'     => fake()->optional(0.6)->phoneNumber(),
            'notes'             => fake()->optional(0.2)->sentence(),
        ];
    }
}
