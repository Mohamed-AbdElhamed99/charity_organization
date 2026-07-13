<?php

namespace App\Services;

use App\Contracts\Services\MeetingMinutesServiceInterface;
use App\DTOs\UpsertMeetingMinutesDTO;
use App\Models\Meeting;
use App\Models\MeetingMinutes;

class MeetingMinutesService implements MeetingMinutesServiceInterface
{
    public function createOrUpdate(Meeting $meeting, UpsertMeetingMinutesDTO $dto): MeetingMinutes
    {
        $existing = $meeting->minutes;

        if ($existing === null) {
            return $meeting->minutes()->create([
                'content' => $dto->content,
                'summary' => $dto->summary,
                'format' => $dto->format,
                'language' => $dto->language,
                'version' => 1,
                'is_approved' => $dto->isApproved,
                'approved_by' => $dto->isApproved ? $dto->userId : null,
                'approved_at' => $dto->isApproved ? now() : null,
                'created_by' => $dto->userId,
            ]);
        }

        $existing->update([
            'content' => $dto->content,
            'summary' => $dto->summary,
            'format' => $dto->format,
            'language' => $dto->language,
            'version' => $existing->version + 1,
            'is_approved' => $dto->isApproved,
            'approved_by' => $dto->isApproved ? $dto->userId : null,
            'approved_at' => $dto->isApproved ? now() : null,
        ]);

        return $existing->fresh(['createdBy', 'approvedBy']);
    }

    public function approve(MeetingMinutes $minutes, int $approvedBy): MeetingMinutes
    {
        $minutes->update([
            'is_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $minutes->fresh(['createdBy', 'approvedBy']);
    }
}
