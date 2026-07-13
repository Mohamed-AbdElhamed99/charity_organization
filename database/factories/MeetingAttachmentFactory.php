<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttachment>
 */
class MeetingAttachmentFactory extends Factory
{
    protected $model = MeetingAttachment::class;

    public function definition(): array
    {
        $fileName = fake()->word().'.pdf';

        return [
            'meeting_id' => Meeting::factory(),
            'file_name' => $fileName,
            'file_path' => 'meetings/1/'.$fileName,
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 2_000_000),
            'description' => fake()->optional(0.5)->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
