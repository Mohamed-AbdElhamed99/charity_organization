<?php

namespace App\DTOs;

use App\Enums\RecurrenceFrequency;

readonly class CreateDonationSubscriptionDTO
{
    /**
     * @param  array<int, DonationAllocationInput>  $allocations
     */
    public function __construct(
        public RecurrenceFrequency $frequency,
        public array $allocations,
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
