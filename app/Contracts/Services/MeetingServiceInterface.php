<?php

namespace App\Contracts\Services;

use App\DTOs\CreateMeetingDecisionDTO;
use App\DTOs\CreateMeetingDTO;
use App\DTOs\StoreMeetingAttachmentDTO;
use App\DTOs\UpdateMeetingDecisionDTO;
use App\DTOs\UpdateMeetingDecisionStatusDTO;
use App\DTOs\UpdateMeetingDTO;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\MeetingDecision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MeetingServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedMeetings(array $filters): LengthAwarePaginator;

    public function findById(int $id): Meeting;

    public function createMeeting(CreateMeetingDTO $dto): Meeting;

    public function updateMeeting(Meeting $meeting, UpdateMeetingDTO $dto): Meeting;

    public function deleteMeeting(Meeting $meeting): void;

    /**
     * @return array<string, mixed>
     */
    public function generatePrintReport(Meeting $meeting, string $format = 'standard'): array;

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    public function createDecision(Meeting $meeting, CreateMeetingDecisionDTO $dto): MeetingDecision;

    public function updateDecision(MeetingDecision $decision, UpdateMeetingDecisionDTO $dto): MeetingDecision;

    public function updateDecisionStatus(MeetingDecision $decision, UpdateMeetingDecisionStatusDTO $dto): MeetingDecision;

    public function deleteDecision(MeetingDecision $decision): void;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorderDecisions(Meeting $meeting, array $orderedIds): void;

    public function storeAttachment(Meeting $meeting, StoreMeetingAttachmentDTO $dto): MeetingAttachment;

    public function deleteAttachment(MeetingAttachment $attachment): void;
}
