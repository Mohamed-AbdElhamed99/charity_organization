<?php

namespace App\DTOs;

readonly class CreateGeneralExpenseDTO
{
    public function __construct(
        public int $accountId,
        public string $name,
        public float $amount,
        public string $expenseDate,
        public ?int $categoryId,
        public ?int $paymentMethodId,
        public ?string $vendorName,
        public bool $isRecurring,
        public ?string $description,
        public ?string $notes,
        public ?string $referenceNumber,
    ) {}
}
