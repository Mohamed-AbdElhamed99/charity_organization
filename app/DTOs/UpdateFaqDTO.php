<?php

namespace App\DTOs;

readonly class UpdateFaqDTO
{
    public function __construct(
        public string $questionAr,
        public ?string $questionEn,
        public string $answerAr,
        public ?string $answerEn,
        public int $sortOrder,
        public bool $isPublished,
    ) {}
}
