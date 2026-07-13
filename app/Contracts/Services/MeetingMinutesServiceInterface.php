<?php

namespace App\Contracts\Services;

use App\DTOs\UpsertMeetingMinutesDTO;
use App\Models\Meeting;
use App\Models\MeetingMinutes;

interface MeetingMinutesServiceInterface
{
    public function createOrUpdate(Meeting $meeting, UpsertMeetingMinutesDTO $dto): MeetingMinutes;

    public function approve(MeetingMinutes $minutes, int $approvedBy): MeetingMinutes;
}
