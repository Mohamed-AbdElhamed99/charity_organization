<?php

namespace Database\Factories;

use App\Enums\IndividualSubtype;
use App\Enums\UserGender;
use App\Models\BeneficiaryFamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryFamilyMember>
 */
class BeneficiaryFamilyMemberFactory extends Factory
{
    protected $model = BeneficiaryFamilyMember::class;

    public function definition(): array
    {
        $subtype = fake()->randomElement(IndividualSubtype::cases());

        return [
            'family_id'            => \App\Models\BeneficiaryFamily::factory(),
            'subtype'              => $subtype,
            'first_name'           => fake()->firstName(),
            'middle_name'          => fake()->optional(0.4)->firstName(),
            'last_name'            => fake()->optional(0.9)->lastName(),
            'gender'               => fake()->randomElement(UserGender::cases()),
            'birthdate'            => $subtype === IndividualSubtype::Child
                ? fake()->dateTimeBetween('-17 years', '-1 year')->format('Y-m-d')
                : fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'national_id'          => fake()->optional(0.7)->numerify('##############'),
            'relation'             => fake()->randomElement([
                'Head', 'Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Dependent',
            ]),
            'health_status'        => fake()->optional(0.6)->randomElement([
                'Good', 'Fair', 'Poor', 'Chronic Illness', 'Disability', 'Healthy',
            ]),
            'education_level'      => fake()->optional(0.6)->randomElement([
                'Illiterate', 'Primary', 'Secondary', 'High School', 'University',
            ]),
            'marital_status'       => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.7)->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'employment_status'    => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.6)->randomElement(['Employed', 'Unemployed', 'Self-Employed', 'Retired']),
            'monthly_income'       => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.5)->randomFloat(2, 0, 3_000),

            // Child-specific
            'date_of_father_death' => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.7)->dateTimeBetween('-10 years', 'now')?->format('Y-m-d')
                : null,
            'school_year'          => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.9)->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'])
                : null,
            'sibling_number'       => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.7)->numberBetween(0, 6)
                : null,
            'behavior_notes'       => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.4)->sentence()
                : null,
        ];
    }
}
