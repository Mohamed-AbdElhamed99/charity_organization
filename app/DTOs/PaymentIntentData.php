<?php

namespace App\DTOs;

readonly class PaymentIntentData
{
    public function __construct(
        public string $id,
        public string $clientSecret,
        public int $amount,
        public string $currency,
    ) {}
}
