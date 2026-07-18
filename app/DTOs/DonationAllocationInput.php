<?php

namespace App\DTOs;

/**
 * One line item of a recurring donation's per-cycle split: either a specific
 * campaign or the general fund, and the cents charged to it each cycle.
 */
readonly class DonationAllocationInput
{
    public function __construct(
        public ?int $campaignId,
        public bool $isGeneral,
        public int $amountCents,
    ) {}
}
