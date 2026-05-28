<?php

namespace Database\Factories;

use App\Enums\AssessmentStatus;
use App\Models\BeneficiaryAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryAssessment>
 */
class BeneficiaryAssessmentFactory extends Factory
{
    protected $model = BeneficiaryAssessment::class;

    public function definition(): array
    {
        $status     = fake()->randomElement(AssessmentStatus::cases());
        $reviewedAt = $status->isReviewed() ? fake()->dateTimeBetween('-6 months', 'now') : null;

        return [
            'beneficiary_id'         => \App\Models\Beneficiary::factory(),
            'assessed_by'            => User::inRandomOrder()->value('id') ?? User::factory(),
            'assessment_date'        => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'purpose'                => fake()->optional(0.9)->randomElement([
                'Medical Treatment', 'Housing Support', 'Food Assistance',
                'Education Support', 'Emergency Aid', 'Orphan Sponsorship',
                'Elderly Care', 'Disability Support',
            ]),
            'housing_details'        => fake()->optional(0.8)->passthrough([
                'type'         => fake()->randomElement(['Apartment', 'House', 'Room', 'Informal']),
                'ownership'    => fake()->randomElement(['Owned', 'Rented', 'Family-Owned']),
                'rooms'        => fake()->numberBetween(1, 5),
                'water_source' => fake()->randomElement(['Tap', 'Well', 'Public Tap']),
                'bathroom'     => fake()->randomElement(['Private', 'Shared', 'None']),
                'condition'    => fake()->randomElement(['Good', 'Fair', 'Poor', 'Needs Renovation']),
            ]),
            'economic_details'       => fake()->optional(0.8)->passthrough([
                'monthly_income'  => fake()->randomFloat(2, 0, 3_000),
                'income_sources'  => fake()->randomElements(['Employment', 'Pension', 'Aid', 'Family Support'], fake()->numberBetween(1, 3)),
                'monthly_expenses' => fake()->randomFloat(2, 200, 2_000),
                'debts'           => fake()->optional(0.3)->randomFloat(2, 0, 20_000),
            ]),
            'health_details'         => fake()->optional(0.8)->passthrough([
                'conditions'     => fake()->optional(0.6)->randomElements(['Diabetes', 'Hypertension', 'Cancer', 'Kidney', 'None'], 1),
                'medications'    => fake()->optional(0.4)->boolean(),
                'hospital_visits' => fake()->numberBetween(0, 10),
                'disability'     => fake()->boolean(20),
            ]),
            'family_details'         => fake()->optional(0.7)->passthrough([
                'marital_status'    => fake()->randomElement(['Widowed', 'Divorced', 'Married', 'Single']),
                'dependents'        => fake()->numberBetween(0, 8),
                'orphaned_children' => fake()->numberBetween(0, 5),
            ]),
            'researcher_opinion'     => fake()->optional(0.8)->paragraph(),
            'recommended_aid_amount' => fake()->optional(0.6)->randomFloat(2, 100, 10_000),
            'status'                 => $status,
            'rejection_reason'       => $status === AssessmentStatus::Rejected
                ? fake()->sentence()
                : null,
            'reviewed_by'            => $reviewedAt
                ? (User::inRandomOrder()->value('id') ?? User::factory())
                : null,
            'reviewed_at'            => $reviewedAt,
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function pending(): static
    {
        return $this->state(fn () => [
            'status'          => AssessmentStatus::Pending,
            'reviewed_by'     => null,
            'reviewed_at'     => null,
            'rejection_reason' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status'          => AssessmentStatus::Approved,
            'reviewed_by'     => User::inRandomOrder()->value('id') ?? User::factory(),
            'reviewed_at'     => fake()->dateTimeBetween('-6 months', 'now'),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'          => AssessmentStatus::Rejected,
            'reviewed_by'     => User::inRandomOrder()->value('id') ?? User::factory(),
            'reviewed_at'     => fake()->dateTimeBetween('-6 months', 'now'),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
