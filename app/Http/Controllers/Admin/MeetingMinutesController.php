<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\MeetingMinutesServiceInterface;
use App\DTOs\UpsertMeetingMinutesDTO;
use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meeting\StoreMeetingMinutesRequest;
use App\Http\Requests\Admin\Meeting\UpdateMeetingMinutesRequest;
use App\Models\Meeting;
use App\Models\MeetingMinutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MeetingMinutesController extends Controller
{
    public function __construct(
        private readonly MeetingMinutesServiceInterface $meetingMinutesService,
    ) {}

    public function store(StoreMeetingMinutesRequest $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validated();

        $this->meetingMinutesService->createOrUpdate($meeting, new UpsertMeetingMinutesDTO(
            content: $validated['content'],
            format: MinutesFormat::from($validated['format']),
            language: MinutesLanguage::from($validated['language']),
            summary: $validated['summary'] ?? null,
            isApproved: (bool) ($validated['is_approved'] ?? false),
            userId: (int) Auth::id(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting minutes saved successfully.')]);

        return back();
    }

    public function update(
        UpdateMeetingMinutesRequest $request,
        Meeting $meeting,
        MeetingMinutes $minutes,
    ): RedirectResponse {
        abort_unless($minutes->meeting_id === $meeting->id, 404);

        $validated = $request->validated();

        $this->meetingMinutesService->createOrUpdate($meeting, new UpsertMeetingMinutesDTO(
            content: $validated['content'],
            format: MinutesFormat::from($validated['format']),
            language: MinutesLanguage::from($validated['language']),
            summary: $validated['summary'] ?? null,
            isApproved: (bool) ($validated['is_approved'] ?? false),
            userId: (int) Auth::id(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting minutes updated successfully.')]);

        return back();
    }

    public function approve(Meeting $meeting, MeetingMinutes $minutes): RedirectResponse
    {
        abort_unless($minutes->meeting_id === $meeting->id, 404);

        $this->meetingMinutesService->approve($minutes, (int) Auth::id());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting minutes approved successfully.')]);

        return back();
    }
}
