<?php

namespace App\DTOs;

readonly class CreateCampaignExpenseDTO
{
    public function __construct(
        public int $campaignId,
        public int $accountId,
        public int $itemId,
        public float $itemPrice,
        public float $quantity,
        public string $expenseDate,
        public int $responsibleUserId,
        public ?string $description,
        public ?string $notes,
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
        public ?int $originalCurrencyId = null,
        public ?float $originalAmount = null,
        public ?float $exchangeRate = null,
    ) {}
}
