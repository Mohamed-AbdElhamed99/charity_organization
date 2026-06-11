<?php

namespace App\DTOs;

readonly class UpdatePaymentMethodDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public bool $isActive,
    ) {}
}
