<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\MeetingServiceInterface;
use App\DTOs\StoreMeetingAttachmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meeting\StoreMeetingAttachmentRequest;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingAttachmentController extends Controller
{
    public function __construct(
        private readonly MeetingServiceInterface $meetingService,
    ) {}

    public function store(StoreMeetingAttachmentRequest $request, Meeting $meeting): RedirectResponse
    {
        $this->meetingService->storeAttachment($meeting, new StoreMeetingAttachmentDTO(
            file: $request->file('file'),
            description: $request->validated('description'),
            uploadedBy: (int) Auth::id(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment uploaded successfully.')]);

        return back();
    }

    public function download(Meeting $meeting, MeetingAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->meeting_id === $meeting->id, 404);

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404);

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(Meeting $meeting, MeetingAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->meeting_id === $meeting->id, 404);

        $this->meetingService->deleteAttachment($attachment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment deleted successfully.')]);

        return back();
    }
}
