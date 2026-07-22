<?php

namespace App\Contracts\Services;

use App\DTOs\CreateAccountDTO;
use App\DTOs\UpdateAccountDTO;
use App\Models\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AccountServiceInterface
{
    public function getPaginatedAccounts(array $filters): LengthAwarePaginator;

    public function createAccount(CreateAccountDTO $dto): BankAccount;

    public function updateAccount(BankAccount $account, UpdateAccountDTO $dto): BankAccount;

    public function deleteAccount(BankAccount $account): void;

    public function restoreAccount(int|string $id): BankAccount;

    public function bulkDelete(array $ids): void;
}
