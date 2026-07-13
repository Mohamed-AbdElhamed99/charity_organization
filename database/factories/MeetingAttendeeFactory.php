<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttendee>
 */
class MeetingAttendeeFactory extends Factory
{
    protected $model = MeetingAttendee::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'name' => fake()->name(),
            'name_en' => fake()->optional(0.2)->name(),
            'title' => fake()->optional(0.7)->jobTitle(),
            'organization' => fake()->optional(0.5)->company(),
            'email' => fake()->optional(0.6)->safeEmail(),
            'phone' => fake()->optional(0.4)->phoneNumber(),
            'attendance_status' => fake()->randomElement(AttendanceStatus::cases()),
            'role' => fake()->randomElement(AttendeeRole::cases()),
            'signature_present' => fake()->boolean(30),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
