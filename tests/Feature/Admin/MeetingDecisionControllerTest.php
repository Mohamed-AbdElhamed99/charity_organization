<?php

namespace Tests\Feature\Admin;

use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Models\Meeting;
use App\Models\MeetingDecision;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingDecisionControllerTest extends TestCase
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

    public function test_authorized_user_can_store_decision(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.meetings.decisions.store', $meeting), [
                'title' => 'Approve new policy',
                'description' => 'The board approves the updated donation policy.',
                'decision_type' => DecisionType::Resolution->value,
                'status' => DecisionStatus::Pending->value,
                'priority' => DecisionPriority::High->value,
                'assigned_to' => 'Operations',
                'due_date' => now()->addMonth()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $decision = MeetingDecision::query()->first();
        $this->assertNotNull($decision);
        $this->assertSame('Approve new policy', $decision->title);
        $this->assertStringStartsWith($meeting->meeting_number.'-D', $decision->decision_number);
    }

    public function test_authorized_user_can_update_decision_status(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $decision = MeetingDecision::factory()->create([
            'meeting_id' => $meeting->id,
            'created_by' => $user->id,
            'status' => DecisionStatus::Pending,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.meetings.decisions.updateStatus', [$meeting, $decision]), [
                'status' => DecisionStatus::Completed->value,
                'completion_notes' => 'Done',
            ])
            ->assertRedirect();

        $decision->refresh();
        $this->assertSame(DecisionStatus::Completed, $decision->status);
        $this->assertNotNull($decision->completion_date);
    }

    public function test_authorized_user_can_reorder_decisions(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $first = MeetingDecision::factory()->create([
            'meeting_id' => $meeting->id,
            'sort_order' => 0,
            'created_by' => $user->id,
        ]);
        $second = MeetingDecision::factory()->create([
            'meeting_id' => $meeting->id,
            'sort_order' => 1,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.meetings.decisions.reorder', $meeting), [
                'ordered_ids' => [$second->id, $first->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    public function test_authorized_user_can_soft_delete_decision(): void
    {
        $user = $this->createAuthorizedUser();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $decision = MeetingDecision::factory()->create([
            'meeting_id' => $meeting->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.meetings.decisions.destroy', [$meeting, $decision]))
            ->assertRedirect();

        $this->assertSoftDeleted('meeting_decisions', ['id' => $decision->id]);
    }
}
