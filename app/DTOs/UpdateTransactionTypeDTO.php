<?php

namespace App\DTOs;

readonly class UpdateTransactionTypeDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public bool $isActive,
    ) {}
}
