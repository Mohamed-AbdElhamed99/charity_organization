<?php

namespace App\DTOs;

readonly class CreateNewsCategoryDTO
{
    public function __construct(
        public string $nameAr,
        public string $nameEn,
        public bool $isActive,
    ) {}
}
