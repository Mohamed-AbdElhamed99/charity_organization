<?php

namespace Database\Factories;

use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Models\Meeting;
use App\Models\MeetingDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingDecision>
 */
class MeetingDecisionFactory extends Factory
{
    protected $model = MeetingDecision::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'decision_type' => fake()->randomElement(DecisionType::cases()),
            'status' => fake()->randomElement(DecisionStatus::cases()),
            'priority' => fake()->randomElement(DecisionPriority::cases()),
            'assigned_to' => fake()->optional(0.7)->name(),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'completion_date' => null,
            'completion_notes' => null,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }
}
