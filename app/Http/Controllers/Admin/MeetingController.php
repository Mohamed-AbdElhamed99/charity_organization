<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\MeetingServiceInterface;
use App\DTOs\CreateMeetingDTO;
use App\DTOs\UpdateMeetingDTO;
use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meeting\StoreMeetingRequest;
use App\Http\Requests\Admin\Meeting\UpdateMeetingRequest;
use App\Http\Resources\Admin\Meeting\MeetingListResource;
use App\Http\Resources\Admin\Meeting\MeetingResource;
use App\Models\Campaign;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function __construct(
        private readonly MeetingServiceInterface $meetingService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'query', 'search', 'status', 'type', 'date_from', 'date_to',
            'campaign_id', 'sort', 'direction', 'page', 'per_page',
        ]);

        $paginator = $this->meetingService->getPaginatedMeetings($filters);
        $meetings = $paginator->toArray();
        $meetings['data'] = MeetingListResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/meetings/meetings-index', [
            'meetings' => $meetings,
            'filters' => $filters,
            'statistics' => $this->meetingService->getStatistics(),
            'typeOptions' => $this->enumOptions(MeetingType::class),
            'statusOptions' => $this->enumOptions(MeetingStatus::class),
            'campaignOptions' => Campaign::query()
                ->orderBy('title_en')
                ->get(['id', 'title_en'])
                ->map(fn (Campaign $campaign) => [
                    'value' => (string) $campaign->id,
                    'label' => $campaign->title_en,
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/meetings/meetings-create', $this->formOptions());
    }

    public function store(StoreMeetingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $meeting = $this->meetingService->createMeeting(new CreateMeetingDTO(
            title: $validated['title'],
            titleEn: $validated['title_en'] ?? null,
            type: MeetingType::from($validated['type']),
            status: MeetingStatus::from($validated['status']),
            meetingDate: $validated['meeting_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'] ?? null,
            location: $validated['location'] ?? null,
            locationType: MeetingLocationType::from($validated['location_type']),
            meetingLink: $validated['meeting_link'] ?? null,
            agenda: $validated['agenda'] ?? null,
            description: $validated['description'] ?? null,
            quorumRequired: isset($validated['quorum_required']) ? (int) $validated['quorum_required'] : null,
            chairperson: $validated['chairperson'] ?? null,
            secretary: $validated['secretary'] ?? null,
            notes: $validated['notes'] ?? null,
            createdBy: (int) Auth::id(),
            campaignIds: array_map('intval', $validated['campaign_ids'] ?? []),
            attendees: $validated['attendees'] ?? [],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting created successfully.')]);

        return redirect()->route('admin.meetings.show', $meeting);
    }

    public function show(Request $request, Meeting $meeting): Response
    {
        $meeting = $this->meetingService->findById($meeting->id);

        return Inertia::render('admin/meetings/meetings-show', [
            'meeting' => (new MeetingResource($meeting))->resolve(),
            'can' => [
                'update' => $request->user()?->can('edit_meetings') ?? false,
                'delete' => $request->user()?->can('delete_meetings') ?? false,
                'approveMinutes' => $request->user()?->can('approve_meeting_minutes') ?? false,
            ],
            ...$this->formOptions(),
            'decisionTypeOptions' => $this->enumOptions(DecisionType::class),
            'decisionStatusOptions' => $this->enumOptions(DecisionStatus::class),
            'decisionPriorityOptions' => $this->enumOptions(DecisionPriority::class),
            'minutesFormatOptions' => $this->enumOptions(MinutesFormat::class),
            'minutesLanguageOptions' => $this->enumOptions(MinutesLanguage::class),
        ]);
    }

    public function edit(Meeting $meeting): Response
    {
        $meeting = $this->meetingService->findById($meeting->id);

        return Inertia::render('admin/meetings/meetings-edit', [
            'meeting' => (new MeetingResource($meeting))->resolve(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validated();

        $this->meetingService->updateMeeting($meeting, new UpdateMeetingDTO(
            title: $validated['title'],
            titleEn: $validated['title_en'] ?? null,
            type: MeetingType::from($validated['type']),
            status: MeetingStatus::from($validated['status']),
            meetingDate: $validated['meeting_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'] ?? null,
            location: $validated['location'] ?? null,
            locationType: MeetingLocationType::from($validated['location_type']),
            meetingLink: $validated['meeting_link'] ?? null,
            agenda: $validated['agenda'] ?? null,
            description: $validated['description'] ?? null,
            quorumRequired: isset($validated['quorum_required']) ? (int) $validated['quorum_required'] : null,
            quorumMet: (bool) ($validated['quorum_met'] ?? $meeting->quorum_met),
            chairperson: $validated['chairperson'] ?? null,
            secretary: $validated['secretary'] ?? null,
            notes: $validated['notes'] ?? null,
            updatedBy: (int) Auth::id(),
            campaignIds: array_map('intval', $validated['campaign_ids'] ?? []),
            attendees: $validated['attendees'] ?? [],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting updated successfully.')]);

        return redirect()->route('admin.meetings.show', $meeting);
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $this->meetingService->deleteMeeting($meeting);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meeting deleted successfully.')]);

        return redirect()->route('admin.meetings.index');
    }

    public function print(Request $request, Meeting $meeting): Response
    {
        $format = $request->string('format', 'standard')->toString();
        $report = $this->meetingService->generatePrintReport($meeting, $format);

        return Inertia::render('admin/meetings/meetings-print', [
            'report' => [
                'format' => $report['format'],
                'organization' => $report['organization'],
                'meeting' => (new MeetingResource($report['meeting']))->resolve(),
                'attended_count' => $report['attended_count'],
                'quorum_required' => $report['quorum_required'],
                'quorum_met' => $report['quorum_met'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'campaignOptions' => Campaign::query()
                ->orderBy('title_en')
                ->get(['id', 'title_en'])
                ->map(fn (Campaign $campaign) => [
                    'value' => (string) $campaign->id,
                    'label' => $campaign->title_en,
                ])
                ->values(),
            'typeOptions' => $this->enumOptions(MeetingType::class),
            'statusOptions' => $this->enumOptions(MeetingStatus::class),
            'locationTypeOptions' => $this->enumOptions(MeetingLocationType::class),
            'attendeeRoleOptions' => $this->enumOptions(AttendeeRole::class),
            'attendanceStatusOptions' => $this->enumOptions(AttendanceStatus::class),
        ];
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : $case->value,
            ])
            ->values()
            ->all();
    }
}
