<?php

namespace Database\Factories;

use App\Models\BeneficiaryUserAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryUserAccess>
 *
 * All beneficiary-profile field names that can be granted.
 * Mirrors the columns on beneficiary_individuals / beneficiary_families / beneficiary_organizations.
 */
class BeneficiaryUserAccessFactory extends Factory
{
    protected $model = BeneficiaryUserAccess::class;

    /** Full list of grantable field names */
    public const ALL_FIELDS = [
        'first_name', 'middle_name', 'last_name', 'gender', 'birthdate',
        'national_id', 'phone', 'address', 'country_id', 'state_id',
        'health_status', 'education_level', 'marital_status',
        'employment_status', 'monthly_income',
        'date_of_father_death', 'school_year', 'sibling_number', 'behavior_notes',
        'household_name', 'social_status', 'total_members',
        'housing_type', 'housing_ownership', 'monthly_rent',
        'organization_type', 'charity_number', 'contact_person', 'contact_phone',
    ];

    public function definition(): array
    {
        // Randomly allow a subset of fields (2 to all)
        $allowedFields = fake()->randomElements(
            static::ALL_FIELDS,
            fake()->numberBetween(2, count(static::ALL_FIELDS))
        );

        // Expiry: 60% get an expiry, 40% are permanent
        $expiresInSeconds = fake()->boolean(60)
            ? fake()->randomElement([
                86_400,        // 1 day
                604_800,       // 1 week
                2_592_000,     // 30 days
                7_776_000,     // 90 days
                31_536_000,    // 1 year
            ])
            : null;

        return [
            'beneficiary_id'     => \App\Models\Beneficiary::factory(),
            'user_id'            => User::inRandomOrder()->value('id') ?? User::factory(),
            'granted_by'         => User::inRandomOrder()->value('id') ?? User::factory(),
            'allowed_fields'     => $allowedFields,
            'expires_in_seconds' => $expiresInSeconds,
            'granted_at'         => fake()->dateTimeBetween('-6 months', 'now'),
            'grant_reason'       => fake()->optional(0.5)->sentence(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function permanent(): static
    {
        return $this->state(fn () => ['expires_in_seconds' => null]);
    }

    public function expiredAccess(): static
    {
        return $this->state(fn () => [
            'expires_in_seconds' => 86_400, // 1 day
            'granted_at'         => now()->subDays(5), // granted 5 days ago → expired
        ]);
    }

    public function allFields(): static
    {
        return $this->state(fn () => ['allowed_fields' => static::ALL_FIELDS]);
    }

    public function basicFields(): static
    {
        return $this->state(fn () => [
            'allowed_fields' => ['first_name', 'last_name', 'phone', 'address'],
        ]);
    }
}
