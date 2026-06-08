<?php

namespace App\DTOs;

readonly class UpdateLegalDocumentDTO
{
    public function __construct(
        public string $titleAr,
        public ?string $titleEn,
        public string $bodyAr,
        public ?string $bodyEn,
    ) {}
}
