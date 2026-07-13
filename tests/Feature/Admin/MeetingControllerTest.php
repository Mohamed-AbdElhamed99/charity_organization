<?php

namespace Tests\Feature\Admin;

use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(array $permissions = ['view_meetings', 'create_meetings', 'edit_meetings', 'delete_meetings']): User
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Board Quarterly Review',
            'title_en' => null,
            'type' => MeetingType::Board->value,
            'status' => MeetingStatus::Scheduled->value,
            'meeting_date' => now()->addWeek()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => 'Main Office',
            'location_type' => MeetingLocationType::Physical->value,
            'meeting_link' => null,
            'agenda' => "1. Opening\n2. Review",
            'description' => 'Quarterly board meeting',
            'quorum_required' => 3,
            'chairperson' => 'Jane Doe',
            'secretary' => 'John Smith',
            'notes' => null,
            'campaign_ids' => [],
            'attendees' => [
                [
                    'name' => 'Jane Doe',
                    'attendance_status' => 'attended',
                    'role' => 'chair',
                ],
                [
                    'name' => 'John Smith',
                    'attendance_status' => 'attended',
                    'role' => 'secretary',
                ],
            ],
        ], $overrides);
    }

    public function test_authorized_user_can_view_meetings_index(): void
    {
        $user = $this->createAuthorizedUser(['view_meetings']);
        Meeting::factory()->count(2)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.meetings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/meetings/meetings-index')
                ->has('meetings.data', 2)
                ->has('statistics')
            );
    }

    public function test_unauthorized_user_cannot_view_meetings_index(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.meetings.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_meeting(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.meetings.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'title' => 'Board Quarterly Review',
            'type' => MeetingType::Board->value,
            'created_by' => $user->id,
        ]);

        $meeting = Meeting::query()->first();
        $this->assertNotNull($meeting);
        $this->assertStringStartsWith('MTG-'.now()->year.'-', $meeting->meeting_number);
        $this->assertDatabaseCount('meeting_attendees', 2);
    }

    public function test_authorized_user_can_view_meeting_show(): void
    {
        $user = $this->createAuthorizedUser(['view_meetings']);
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.meetings.show', $meeting))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/meetings/meetings-show')
                ->where('meeting.id', $meeting->id)
            );
    }

    public function test_authorized_user_can_update_meeting(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->put(route('admin.meetings.update', $meeting), $this->validPayload([
                'title' => 'Updated Meeting Title',
                'status' => MeetingStatus::Completed->value,
            ]))
            ->assertRedirect(route('admin.meetings.show', $meeting));

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'title' => 'Updated Meeting Title',
            'status' => MeetingStatus::Completed->value,
        ]);
    }

    public function test_authorized_user_can_soft_delete_meeting(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('admin.meetings.destroy', $meeting))
            ->assertRedirect(route('admin.meetings.index'));

        $this->assertSoftDeleted('meetings', ['id' => $meeting->id]);
    }

    public function test_authorized_user_can_view_print_page(): void
    {
        $user = $this->createAuthorizedUser(['view_meetings']);
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.meetings.print', $meeting))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/meetings/meetings-print')
                ->has('report.meeting')
            );
    }
}
