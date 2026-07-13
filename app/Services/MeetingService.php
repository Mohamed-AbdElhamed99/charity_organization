<?php

namespace App\Services;

use App\Contracts\Services\MeetingServiceInterface;
use App\DTOs\CreateMeetingDecisionDTO;
use App\DTOs\CreateMeetingDTO;
use App\DTOs\StoreMeetingAttachmentDTO;
use App\DTOs\UpdateMeetingDecisionDTO;
use App\DTOs\UpdateMeetingDecisionStatusDTO;
use App\DTOs\UpdateMeetingDTO;
use App\Enums\AttendanceStatus;
use App\Enums\DecisionStatus;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\MeetingDecision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingService implements MeetingServiceInterface
{
    public function getPaginatedMeetings(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;
        $sort = $filters['sort'] ?? 'meeting_date';
        $direction = $filters['direction'] ?? 'desc';

        $allowedSorts = ['meeting_date', 'created_at', 'title', 'status', 'type'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'meeting_date';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return Meeting::query()
            ->with(['campaigns:id,title_en,slug', 'createdBy:id,name'])
            ->withCount(['attendees', 'decisions'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('title_en', 'like', "%{$query}%")
                        ->orWhere('meeting_number', 'like', "%{$query}%")
                        ->orWhere('location', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];
                $builder->whereIn('status', $statuses);
            })
            ->when($type, function ($builder) use ($type) {
                $types = is_array($type) ? $type : [$type];
                $builder->whereIn('type', $types);
            })
            ->when($dateFrom, fn ($builder) => $builder->whereDate('meeting_date', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('meeting_date', '<=', $dateTo))
            ->when($campaignId, function ($builder) use ($campaignId) {
                $builder->whereHas('campaigns', fn ($q) => $q->where('campaigns.id', $campaignId));
            })
            ->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function findById(int $id): Meeting
    {
        return Meeting::query()
            ->with([
                'minutes.createdBy:id,name',
                'minutes.approvedBy:id,name',
                'decisions',
                'attendees',
                'campaigns:id,title_en,title_ar,slug',
                'attachments.uploadedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount(['attendees', 'decisions'])
            ->findOrFail($id);
    }

    public function createMeeting(CreateMeetingDTO $dto): Meeting
    {
        return DB::transaction(function () use ($dto) {
            $meeting = Meeting::create([
                'title' => $dto->title,
                'title_en' => $dto->titleEn,
                'type' => $dto->type,
                'status' => $dto->status,
                'meeting_date' => $dto->meetingDate,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'location' => $dto->location,
                'location_type' => $dto->locationType,
                'meeting_link' => $dto->meetingLink,
                'agenda' => $dto->agenda,
                'description' => $dto->description,
                'quorum_required' => $dto->quorumRequired,
                'chairperson' => $dto->chairperson,
                'secretary' => $dto->secretary,
                'notes' => $dto->notes,
                'created_by' => $dto->createdBy,
                'updated_by' => $dto->createdBy,
            ]);

            $this->syncCampaigns($meeting, $dto->campaignIds);
            $this->syncAttendees($meeting, $dto->attendees);
            $this->refreshQuorumMet($meeting);

            return $meeting->fresh([
                'attendees',
                'campaigns:id,title_en,slug',
                'createdBy:id,name',
            ]);
        });
    }

    public function updateMeeting(Meeting $meeting, UpdateMeetingDTO $dto): Meeting
    {
        return DB::transaction(function () use ($meeting, $dto) {
            $meeting->update([
                'title' => $dto->title,
                'title_en' => $dto->titleEn,
                'type' => $dto->type,
                'status' => $dto->status,
                'meeting_date' => $dto->meetingDate,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'location' => $dto->location,
                'location_type' => $dto->locationType,
                'meeting_link' => $dto->meetingLink,
                'agenda' => $dto->agenda,
                'description' => $dto->description,
                'quorum_required' => $dto->quorumRequired,
                'quorum_met' => $dto->quorumMet,
                'chairperson' => $dto->chairperson,
                'secretary' => $dto->secretary,
                'notes' => $dto->notes,
                'updated_by' => $dto->updatedBy,
            ]);

            $this->syncCampaigns($meeting, $dto->campaignIds);
            $this->syncAttendees($meeting, $dto->attendees);
            $this->refreshQuorumMet($meeting);

            return $meeting->fresh([
                'attendees',
                'campaigns:id,title_en,slug',
                'createdBy:id,name',
            ]);
        });
    }

    public function deleteMeeting(Meeting $meeting): void
    {
        $meeting->delete();
    }

    public function generatePrintReport(Meeting $meeting, string $format = 'standard'): array
    {
        $meeting->loadMissing([
            'minutes.approvedBy:id,name',
            'decisions',
            'attendees',
            'campaigns:id,title_en,title_ar,slug',
            'createdBy:id,name',
        ]);

        return [
            'format' => $format,
            'organization' => config('app.name', 'New Egypt Group'),
            'meeting' => $meeting,
            'attendees' => $meeting->attendees,
            'minutes' => $meeting->minutes,
            'decisions' => $meeting->decisions,
            'campaigns' => $meeting->campaigns,
            'attended_count' => $meeting->attended_count,
            'quorum_required' => $meeting->quorum_required,
            'quorum_met' => $meeting->quorum_met,
        ];
    }

    public function getStatistics(): array
    {
        $byStatus = [];
        foreach (MeetingStatus::cases() as $status) {
            $byStatus[$status->value] = Meeting::query()->where('status', $status)->count();
        }

        $byType = [];
        foreach (MeetingType::cases() as $type) {
            $byType[$type->value] = Meeting::query()->where('type', $type)->count();
        }

        return [
            'total' => Meeting::query()->count(),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'upcoming_count' => Meeting::query()->upcoming()->count(),
            'decisions_pending' => MeetingDecision::query()
                ->whereIn('status', [DecisionStatus::Pending, DecisionStatus::InProgress])
                ->count(),
        ];
    }

    public function createDecision(Meeting $meeting, CreateMeetingDecisionDTO $dto): MeetingDecision
    {
        $maxOrder = (int) $meeting->decisions()->max('sort_order');

        return $meeting->decisions()->create([
            'title' => $dto->title,
            'description' => $dto->description,
            'decision_type' => $dto->decisionType,
            'status' => $dto->status,
            'priority' => $dto->priority,
            'assigned_to' => $dto->assignedTo,
            'due_date' => $dto->dueDate,
            'sort_order' => $maxOrder + 1,
            'created_by' => $dto->createdBy,
        ]);
    }

    public function updateDecision(MeetingDecision $decision, UpdateMeetingDecisionDTO $dto): MeetingDecision
    {
        $decision->update([
            'title' => $dto->title,
            'description' => $dto->description,
            'decision_type' => $dto->decisionType,
            'status' => $dto->status,
            'priority' => $dto->priority,
            'assigned_to' => $dto->assignedTo,
            'due_date' => $dto->dueDate,
            'completion_date' => $dto->completionDate,
            'completion_notes' => $dto->completionNotes,
        ]);

        return $decision->fresh();
    }

    public function updateDecisionStatus(MeetingDecision $decision, UpdateMeetingDecisionStatusDTO $dto): MeetingDecision
    {
        $data = [
            'status' => $dto->status,
            'completion_notes' => $dto->completionNotes,
        ];

        if ($dto->status === DecisionStatus::Completed) {
            $data['completion_date'] = $dto->completionDate ?? now()->toDateString();
        } elseif ($dto->completionDate !== null) {
            $data['completion_date'] = $dto->completionDate;
        }

        $decision->update($data);

        return $decision->fresh();
    }

    public function deleteDecision(MeetingDecision $decision): void
    {
        $decision->delete();
    }

    public function reorderDecisions(Meeting $meeting, array $orderedIds): void
    {
        DB::transaction(function () use ($meeting, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                MeetingDecision::query()
                    ->where('meeting_id', $meeting->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    public function storeAttachment(Meeting $meeting, StoreMeetingAttachmentDTO $dto): MeetingAttachment
    {
        $file = $dto->file;
        $path = $file->store("meetings/{$meeting->id}", 'public');

        return $meeting->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType() ?: $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'description' => $dto->description,
            'uploaded_by' => $dto->uploadedBy,
        ]);
    }

    public function deleteAttachment(MeetingAttachment $attachment): void
    {
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();
    }

    /**
     * @param  array<int, int>  $campaignIds
     */
    public function syncCampaigns(Meeting $meeting, array $campaignIds): void
    {
        $meeting->campaigns()->sync($campaignIds);
    }

    /**
     * @param  array<int, array<string, mixed>>  $attendees
     */
    public function syncAttendees(Meeting $meeting, array $attendees): void
    {
        $keptIds = [];

        foreach ($attendees as $attendeeData) {
            $payload = [
                'name' => $attendeeData['name'],
                'name_en' => $attendeeData['name_en'] ?? null,
                'title' => $attendeeData['title'] ?? null,
                'organization' => $attendeeData['organization'] ?? null,
                'email' => $attendeeData['email'] ?? null,
                'phone' => $attendeeData['phone'] ?? null,
                'attendance_status' => $attendeeData['attendance_status'],
                'role' => $attendeeData['role'],
                'signature_present' => (bool) ($attendeeData['signature_present'] ?? false),
                'notes' => $attendeeData['notes'] ?? null,
            ];

            if (! empty($attendeeData['id'])) {
                $attendee = $meeting->attendees()->whereKey($attendeeData['id'])->first();

                if ($attendee !== null) {
                    $attendee->update($payload);
                    $keptIds[] = $attendee->id;

                    continue;
                }
            }

            $created = $meeting->attendees()->create($payload);
            $keptIds[] = $created->id;
        }

        $meeting->attendees()->whereNotIn('id', $keptIds)->delete();
    }

    private function refreshQuorumMet(Meeting $meeting): void
    {
        if ($meeting->quorum_required === null) {
            return;
        }

        $attended = $meeting->attendees()
            ->where('attendance_status', AttendanceStatus::Attended)
            ->count();

        $meeting->update([
            'quorum_met' => $attended >= $meeting->quorum_required,
        ]);
    }
}
