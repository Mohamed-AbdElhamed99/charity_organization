<?php

namespace App\DTOs;

readonly class UpdateGeneralExpenseCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isActive,
    ) {}
}
