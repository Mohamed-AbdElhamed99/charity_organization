<?php

namespace App\DTOs;

readonly class SetupIntentData
{
    public function __construct(
        public string $setupIntentId,
        public string $clientSecret,
        public string $customerId,
    ) {}
}
