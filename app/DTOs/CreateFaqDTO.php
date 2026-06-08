<?php

namespace App\DTOs;

readonly class CreateFaqDTO
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
