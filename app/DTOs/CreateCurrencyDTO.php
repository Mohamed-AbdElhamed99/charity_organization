<?php

namespace App\DTOs;

readonly class CreateCurrencyDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $symbol,
        public bool $isActive,
        public bool $isDefault,
    ) {}
}
