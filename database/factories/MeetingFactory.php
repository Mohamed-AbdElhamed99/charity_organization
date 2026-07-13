<?php

namespace Database\Factories;

use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        $startHour = fake()->numberBetween(8, 16);

        return [
            'title' => fake()->sentence(4),
            'title_en' => fake()->optional(0.3)->sentence(4),
            'type' => fake()->randomElement(MeetingType::cases()),
            'status' => fake()->randomElement(MeetingStatus::cases()),
            'meeting_date' => fake()->dateTimeBetween('-1 year', '+3 months')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:30', min($startHour + 2, 23)),
            'location' => fake()->optional(0.8)->city(),
            'location_type' => fake()->randomElement(MeetingLocationType::cases()),
            'meeting_link' => fake()->optional(0.3)->url(),
            'agenda' => fake()->optional(0.7)->paragraphs(2, true),
            'description' => fake()->optional(0.5)->paragraph(),
            'quorum_required' => fake()->optional(0.6)->numberBetween(3, 10),
            'quorum_met' => false,
            'chairperson' => fake()->optional(0.8)->name(),
            'secretary' => fake()->optional(0.8)->name(),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => MeetingStatus::Scheduled,
            'meeting_date' => fake()->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => MeetingStatus::Completed,
            'meeting_date' => fake()->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
        ]);
    }
}
