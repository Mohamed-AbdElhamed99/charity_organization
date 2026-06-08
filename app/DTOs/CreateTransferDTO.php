<?php

namespace App\DTOs;

use App\Enums\TransferRecipientType;

readonly class CreateTransferDTO
{
    public function __construct(
        public TransferRecipientType $recipientType,
        public string $recipientName,
        public float $amount,
        public string $transferDate,
        public string $purpose,
        public ?int $campaignId = null,
        public ?string $recipientPhone = null,
        public ?int $beneficiaryId = null,
        public ?int $userId = null,
        public ?string $notes = null,
        public ?int $accountId = null,
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
    ) {}
}
