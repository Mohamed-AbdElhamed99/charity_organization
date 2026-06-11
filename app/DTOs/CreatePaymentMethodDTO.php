<?php

namespace App\DTOs;

readonly class CreatePaymentMethodDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public bool $isActive,
    ) {}
}
