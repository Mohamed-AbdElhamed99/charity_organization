<?php

namespace App\DTOs;

use App\Enums\DecisionStatus;

readonly class UpdateMeetingDecisionStatusDTO
{
    public function __construct(
        public DecisionStatus $status,
        public ?string $completionDate,
        public ?string $completionNotes,
    ) {}
}
