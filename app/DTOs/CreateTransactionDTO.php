<?php

namespace App\DTOs;

use App\Enums\TransactionDirection;
use Illuminate\Http\UploadedFile;

readonly class CreateTransactionDTO
{
    /**
     * @param  array<int, array{currency_code: string, equivalent: float|int|string}>|null  $currencyEquivalents
     * @param  array<string, mixed>|null  $transfer
     * @param  array<int, UploadedFile>|null  $documents
     * @param  array<int, int>  $beneficiaryIds
     * @param  array<int, int>  $campaignIds
     * @param  array<int, int>  $meetingIds
     * @param  array<int, int>  $generalExpenseIds
     */
    public function __construct(
        public int $accountId,
        public int $transactionTypeId,
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
        public ?array $currencyEquivalents = null,
        public ?float $balanceBefore = null,
        public ?float $runningBalance = null,
        public ?array $transfer = null,
        public ?array $documents = null,
        public array $beneficiaryIds = [],
        public array $campaignIds = [],
        public array $meetingIds = [],
        public array $generalExpenseIds = [],
    ) {}
}
