<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Enums\DecisionStatus;
use App\Models\Campaign;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingDecision;
use App\Models\MeetingMinutes;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds sample meetings with attendees and decisions.
 * Depends on: UserSeeder, CampaignSeeder.
 */
class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->info('Skipping MeetingSeeder outside local/testing.');

            return;
        }

        $user = User::query()->first() ?? User::factory()->create();
        $campaignIds = Campaign::query()->inRandomOrder()->limit(10)->pluck('id');

        Meeting::factory()
            ->count(20)
            ->create(['created_by' => $user->id, 'updated_by' => $user->id])
            ->each(function (Meeting $meeting) use ($user, $campaignIds) {
                $attendeeCount = fake()->numberBetween(3, 8);

                MeetingAttendee::factory()
                    ->count($attendeeCount)
                    ->create(['meeting_id' => $meeting->id]);

                MeetingAttendee::factory()->create([
                    'meeting_id' => $meeting->id,
                    'role' => AttendeeRole::Chair,
                    'attendance_status' => AttendanceStatus::Attended,
                    'name' => $meeting->chairperson ?? fake()->name(),
                ]);

                $decisionCount = fake()->numberBetween(1, 3);

                for ($i = 0; $i < $decisionCount; $i++) {
                    MeetingDecision::factory()->create([
                        'meeting_id' => $meeting->id,
                        'sort_order' => $i,
                        'created_by' => $user->id,
                        'status' => fake()->randomElement(DecisionStatus::cases()),
                    ]);
                }

                if (fake()->boolean(60)) {
                    MeetingMinutes::factory()->create([
                        'meeting_id' => $meeting->id,
                        'created_by' => $user->id,
                        'is_approved' => fake()->boolean(40),
                        'approved_by' => fake()->boolean(40) ? $user->id : null,
                        'approved_at' => fake()->boolean(40) ? now() : null,
                    ]);
                }

                if ($campaignIds->isNotEmpty() && fake()->boolean(70)) {
                    $meeting->campaigns()->sync(
                        $campaignIds->random(fake()->numberBetween(1, min(3, $campaignIds->count())))->all()
                    );
                }
            });

        $this->command->info('✅ Meetings seeded.');
    }
}
