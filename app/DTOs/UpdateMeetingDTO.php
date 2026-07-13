<?php

namespace App\DTOs;

use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;

readonly class UpdateMeetingDTO
{
    /**
     * @param  array<int, int>  $campaignIds
     * @param  array<int, array<string, mixed>>  $attendees
     */
    public function __construct(
        public string $title,
        public ?string $titleEn,
        public MeetingType $type,
        public MeetingStatus $status,
        public string $meetingDate,
        public string $startTime,
        public ?string $endTime,
        public ?string $location,
        public MeetingLocationType $locationType,
        public ?string $meetingLink,
        public ?string $agenda,
        public ?string $description,
        public ?int $quorumRequired,
        public bool $quorumMet,
        public ?string $chairperson,
        public ?string $secretary,
        public ?string $notes,
        public int $updatedBy,
        public array $campaignIds = [],
        public array $attendees = [],
    ) {}
}
