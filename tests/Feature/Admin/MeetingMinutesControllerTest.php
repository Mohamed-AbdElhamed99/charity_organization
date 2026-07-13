<?php

namespace Tests\Feature\Admin;

use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use App\Models\Meeting;
use App\Models\MeetingMinutes;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingMinutesControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(array $permissions = ['view_meetings', 'edit_meetings', 'approve_meeting_minutes']): User
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_authorized_user_can_store_minutes(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.meetings.minutes.store', $meeting), [
                'content' => 'The board reviewed the quarterly report.',
                'summary' => 'Quarterly review completed.',
                'format' => MinutesFormat::Standard->value,
                'language' => MinutesLanguage::En->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_minutes', [
            'meeting_id' => $meeting->id,
            'content' => 'The board reviewed the quarterly report.',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_update_minutes(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $minutes = MeetingMinutes::factory()->create([
            'meeting_id' => $meeting->id,
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->put(route('admin.meetings.minutes.update', [$meeting, $minutes]), [
                'content' => 'Updated minutes content.',
                'summary' => 'Updated summary',
                'format' => MinutesFormat::Formal->value,
                'language' => MinutesLanguage::En->value,
            ])
            ->assertRedirect();

        $minutes->refresh();
        $this->assertSame('Updated minutes content.', $minutes->content);
        $this->assertSame(2, $minutes->version);
    }

    public function test_authorized_user_can_approve_minutes(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $minutes = MeetingMinutes::factory()->create([
            'meeting_id' => $meeting->id,
            'created_by' => $user->id,
            'is_approved' => false,
        ]);

        $this->actingAs($user)
            ->post(route('admin.meetings.minutes.approve', [$meeting, $minutes]))
            ->assertRedirect();

        $minutes->refresh();
        $this->assertTrue($minutes->is_approved);
        $this->assertSame($user->id, $minutes->approved_by);
        $this->assertNotNull($minutes->approved_at);
    }
}
