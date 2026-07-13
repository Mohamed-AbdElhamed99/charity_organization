<?php

namespace App\DTOs;

use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;

readonly class UpdateMeetingDecisionDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public DecisionType $decisionType,
        public DecisionStatus $status,
        public DecisionPriority $priority,
        public ?string $assignedTo,
        public ?string $dueDate,
        public ?string $completionDate,
        public ?string $completionNotes,
    ) {}
}
