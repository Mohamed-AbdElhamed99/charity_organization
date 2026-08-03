<?php

namespace App\DTOs;

readonly class CreateTransactionTypeDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public bool $isActive,
    ) {}
}
