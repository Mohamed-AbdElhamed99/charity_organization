<?php

namespace App\DTOs;

readonly class UpdateDonorProfileDTO
{
    public function __construct(
        public string $type,
        public ?string $organizationName,
        public ?string $address,
        public ?int $countryId,
        public ?int $stateId,
        public ?string $notes,
    ) {}
}
