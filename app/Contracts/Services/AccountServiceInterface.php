<?php

namespace App\Contracts\Services;

use App\DTOs\CreateAccountDTO;
use App\DTOs\UpdateAccountDTO;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AccountServiceInterface
{
    public function getPaginatedAccounts(array $filters): LengthAwarePaginator;

    public function createAccount(CreateAccountDTO $dto): Account;

    public function updateAccount(Account $account, UpdateAccountDTO $dto): Account;

    public function deleteAccount(Account $account): void;

    public function restoreAccount(int|string $id): Account;

    public function bulkDelete(array $ids): void;
}
