<?php

namespace App\DTOs;

readonly class UpdateCurrencyDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $symbol,
        public bool $isActive,
        public bool $isDefault,
    ) {}
}
