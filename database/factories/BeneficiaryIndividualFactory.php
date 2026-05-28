<?php

namespace Database\Factories;

use App\Enums\IndividualSubtype;
use App\Enums\UserGender;
use App\Models\BeneficiaryIndividual;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryIndividual>
 */
class BeneficiaryIndividualFactory extends Factory
{
    protected $model = BeneficiaryIndividual::class;

    public function definition(): array
    {
        $subtype = fake()->randomElement(IndividualSubtype::cases());
        $country = Country::inRandomOrder()->first();
        $state   = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        return [
            'beneficiary_id'       => \App\Models\Beneficiary::factory(),
            'subtype'              => $subtype,
            'first_name'           => fake()->firstName(),
            'middle_name'          => fake()->optional(0.5)->firstName(),
            'last_name'            => fake()->lastName(),
            'gender'               => fake()->randomElement(UserGender::cases()),
            'birthdate'            => $subtype === IndividualSubtype::Child
                ? fake()->dateTimeBetween('-17 years', '-1 year')->format('Y-m-d')
                : fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'national_id'          => fake()->unique()->numerify('##############'),
            'phone'                => fake()->optional(0.7)->phoneNumber(),
            'address'              => fake()->optional(0.7)->address(),
            'country_id'           => $country?->id,
            'state_id'             => $state?->id,
            'health_status'        => fake()->optional(0.7)->randomElement([
                'Good', 'Fair', 'Poor',
                'Diabetes', 'Heart Disease', 'Cancer', 'Kidney Failure',
                'Disability', 'Healthy',
            ]),
            'education_level'      => fake()->optional(0.6)->randomElement([
                'Illiterate', 'Primary', 'Secondary', 'High School', 'University', 'Postgraduate',
            ]),
            'marital_status'       => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.8)->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'employment_status'    => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.7)->randomElement(['Employed', 'Unemployed', 'Self-Employed', 'Retired', 'Student']),
            'monthly_income'       => $subtype === IndividualSubtype::Child
                ? null
                : fake()->optional(0.6)->randomFloat(2, 0, 5_000),

            // Child-specific — only populated when subtype = child
            'date_of_father_death' => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.8)->dateTimeBetween('-10 years', 'now')?->format('Y-m-d')
                : null,
            'school_year'          => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.9)->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'])
                : null,
            'sibling_number'       => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.8)->numberBetween(0, 8)
                : null,
            'behavior_notes'       => $subtype === IndividualSubtype::Child
                ? fake()->optional(0.5)->sentence()
                : null,
            'notes'                => fake()->optional(0.2)->sentence(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function child(): static
    {
        return $this->state(fn () => [
            'subtype'              => IndividualSubtype::Child,
            'birthdate'            => fake()->dateTimeBetween('-17 years', '-1 year')->format('Y-m-d'),
            'marital_status'       => null,
            'employment_status'    => null,
            'monthly_income'       => null,
            'date_of_father_death' => fake()->optional(0.8)->dateTimeBetween('-10 years', 'now')?->format('Y-m-d'),
            'school_year'          => fake()->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']),
            'sibling_number'       => fake()->numberBetween(0, 6),
        ]);
    }

    public function adult(): static
    {
        return $this->state(fn () => [
            'subtype'              => IndividualSubtype::Adult,
            'birthdate'            => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'date_of_father_death' => null,
            'school_year'          => null,
            'sibling_number'       => null,
            'behavior_notes'       => null,
        ]);
    }
}
