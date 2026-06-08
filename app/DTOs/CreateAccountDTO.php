<?php

namespace App\DTOs;

use App\Enums\AccountType;

readonly class CreateAccountDTO
{
    public function __construct(
        public string $name,
        public ?string $accountNumber,
        public ?string $bankName,
        public ?string $bankBranch,
        public int $currencyId,
        public AccountType $type,
        public float $openingBalance,
        public bool $isActive,
        public ?string $notes,
    ) {}
}
