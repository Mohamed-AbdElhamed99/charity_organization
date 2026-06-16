<?php

namespace App\DTOs;

readonly class CreateDonationIntentDTO
{
    public function __construct(
        public ?int $campaignId,
        public bool $isGeneral,
        public int $amountCents,
        public bool $donorCoversFee,
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public ?int $countryId,
        public bool $isAnonymous,
        public ?string $donorMessage,
    ) {}
}
