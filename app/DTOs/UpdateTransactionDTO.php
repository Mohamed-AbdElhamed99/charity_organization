<?php

namespace App\DTOs;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;

readonly class UpdateTransactionDTO
{
    public function __construct(
        public int $accountId,
        public TransactionType $transactionType,
        public TransactionDirection $direction,
        public int $currencyId,
        public float $grossAmount,
        public float $feeAmount,
        public string $transactionDate,
        public ?string $referenceNumber,
        public ?string $description,
        public ?string $notes,
        public ?int $paymentMethodId,
    ) {}
}
