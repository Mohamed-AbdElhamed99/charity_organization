<?php

namespace App\DTOs;

readonly class CreateCampaignCategoryDTO
{
    public function __construct(
        public string $nameAr,
        public string $nameEn,
        public ?string $description,
        public bool $isActive,
    ) {}
}
