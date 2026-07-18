<?php

namespace App\DTOs;

readonly class PaymentMethodData
{
    public function __construct(
        public string $id,
        public string $brand,
        public string $last4,
        public int $expMonth,
        public int $expYear,
    ) {}
}
