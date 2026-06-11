<?php

namespace App\DTOs;

readonly class UpdateGeneralExpenseDTO
{
    public function __construct(
        public ?int $categoryId,
        public string $name,
        public ?string $vendorName,
        public bool $isRecurring,
        public ?string $notes,
    ) {}
}
