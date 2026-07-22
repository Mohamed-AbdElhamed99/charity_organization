<?php

namespace App\DTOs;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Illuminate\Http\UploadedFile;

readonly class CreateTransactionDTO
{
    /**
     * @param  array<string, mixed>|null  $transfer
     * @param  array<int, UploadedFile>|null  $documents
     */
    public function __construct(
        public int $accountId,
        public TransactionType $transactionType,
        public TransactionDirection $direction,
        public float $grossAmount,
        public float $feeAmount,
        public string $transactionDate,
        public ?string $referenceNumber,
        public ?string $description,
        public ?string $notes,
        public ?int $paymentMethodId,
        public int $createdBy,
        public ?int $originalCurrencyId = null,
        public ?float $originalAmount = null,
        public ?float $exchangeRate = null,
        public ?array $transfer = null,
        public ?array $documents = null,
    ) {}
}
