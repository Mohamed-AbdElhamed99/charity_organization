<?php

namespace App\DTOs;

use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;

readonly class UpsertMeetingMinutesDTO
{
    public function __construct(
        public string $content,
        public MinutesFormat $format,
        public MinutesLanguage $language,
        public ?string $summary,
        public bool $isApproved,
        public int $userId,
    ) {}
}
