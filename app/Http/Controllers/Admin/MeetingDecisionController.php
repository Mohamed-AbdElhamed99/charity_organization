<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\MeetingServiceInterface;
use App\DTOs\CreateMeetingDecisionDTO;
use App\DTOs\UpdateMeetingDecisionDTO;
use App\DTOs\UpdateMeetingDecisionStatusDTO;
use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meeting\ReorderMeetingDecisionsRequest;
use App\Http\Requests\Admin\Meeting\StoreMeetingDecisionRequest;
use App\Http\Requests\Admin\Meeting\UpdateMeetingDecisionRequest;
use App\Http\Requests\Admin\Meeting\UpdateMeetingDecisionStatusRequest;
use App\Models\Meeting;
use App\Models\MeetingDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MeetingDecisionController extends Controller
{
    public function __construct(
        private readonly MeetingServiceInterface $meetingService,
    ) {}

    public function store(StoreMeetingDecisionRequest $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validated();

        $this->meetingService->createDecision($meeting, new CreateMeetingDecisionDTO(
            title: $validated['title'],
            description: $validated['description'],
            decisionType: DecisionType::from($validated['decision_type']),
            status: DecisionStatus::from($validated['status']),
            priority: DecisionPriority::from($validated['priority']),
            assignedTo: $validated['assigned_to'] ?? null,
            dueDate: $validated['due_date'] ?? null,
            createdBy: (int) Auth::id(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision created successfully.')]);

        return back();
    }

    public function update(
        UpdateMeetingDecisionRequest $request,
        Meeting $meeting,
        MeetingDecision $decision,
    ): RedirectResponse {
        abort_unless($decision->meeting_id === $meeting->id, 404);

        $validated = $request->validated();

        $this->meetingService->updateDecision($decision, new UpdateMeetingDecisionDTO(
            title: $validated['title'],
            description: $validated['description'],
            decisionType: DecisionType::from($validated['decision_type']),
            status: DecisionStatus::from($validated['status']),
            priority: DecisionPriority::from($validated['priority']),
            assignedTo: $validated['assigned_to'] ?? null,
            dueDate: $validated['due_date'] ?? null,
            completionDate: $validated['completion_date'] ?? null,
            completionNotes: $validated['completion_notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision updated successfully.')]);

        return back();
    }

    public function updateStatus(
        UpdateMeetingDecisionStatusRequest $request,
        Meeting $meeting,
        MeetingDecision $decision,
    ): RedirectResponse {
        abort_unless($decision->meeting_id === $meeting->id, 404);

        $validated = $request->validated();

        $this->meetingService->updateDecisionStatus($decision, new UpdateMeetingDecisionStatusDTO(
            status: DecisionStatus::from($validated['status']),
            completionDate: $validated['completion_date'] ?? null,
            completionNotes: $validated['completion_notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision status updated successfully.')]);

        return back();
    }

    public function destroy(Meeting $meeting, MeetingDecision $decision): RedirectResponse
    {
        abort_unless($decision->meeting_id === $meeting->id, 404);

        $this->meetingService->deleteDecision($decision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision deleted successfully.')]);

        return back();
    }

    public function reorder(ReorderMeetingDecisionsRequest $request, Meeting $meeting): JsonResponse
    {
        $this->meetingService->reorderDecisions($meeting, $request->validated('ordered_ids'));

        return response()->json(['success' => true]);
    }
}
