<?php

namespace App\DTOs;

readonly class CreateDonorProfileDTO
{
    public function __construct(
        public int $userId,
        public string $type,
        public ?string $organizationName,
        public ?string $address,
        public ?int $countryId,
        public ?int $stateId,
        public ?string $notes,
    ) {}
}
