<?php

namespace Database\Factories;

use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use App\Models\Meeting;
use App\Models\MeetingMinutes;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingMinutes>
 */
class MeetingMinutesFactory extends Factory
{
    protected $model = MeetingMinutes::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'content' => fake()->paragraphs(4, true),
            'summary' => fake()->optional(0.7)->paragraph(),
            'format' => MinutesFormat::Standard,
            'language' => MinutesLanguage::En,
            'version' => 1,
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
