<?php

namespace App\DTOs;

readonly class UpdateCampaignExpenseDTO
{
    public function __construct(
        public ?string $notes,
        public float $residualQuantity,
        public float $residualAmount,
    ) {}
}
