<?php

namespace Tests\Feature\Admin;

use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeetingAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(['view_meetings', 'edit_meetings']);

        return $user;
    }

    public function test_authorized_user_can_upload_attachment(): void
    {
        Storage::fake('public');

        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->create('agenda.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('admin.meetings.attachments.store', $meeting), [
                'file' => $file,
                'description' => 'Meeting agenda',
            ])
            ->assertRedirect();

        $attachment = MeetingAttachment::query()->first();
        $this->assertNotNull($attachment);
        $this->assertSame('agenda.pdf', $attachment->file_name);
        $this->assertSame('Meeting agenda', $attachment->description);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_authorized_user_can_download_attachment(): void
    {
        Storage::fake('public');

        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $path = "meetings/{$meeting->id}/notes.txt";
        Storage::disk('public')->put($path, 'hello');

        $attachment = MeetingAttachment::factory()->create([
            'meeting_id' => $meeting->id,
            'file_name' => 'notes.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 5,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.meetings.attachments.download', [$meeting, $attachment]))
            ->assertOk();
    }

    public function test_authorized_user_can_delete_attachment(): void
    {
        Storage::fake('public');

        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $path = "meetings/{$meeting->id}/notes.txt";
        Storage::disk('public')->put($path, 'hello');

        $attachment = MeetingAttachment::factory()->create([
            'meeting_id' => $meeting->id,
            'file_name' => 'notes.txt',
            'file_path' => $path,
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.meetings.attachments.destroy', [$meeting, $attachment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('meeting_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
