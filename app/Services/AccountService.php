<?php

namespace App\Services;

use App\Contracts\Services\AccountServiceInterface;
use App\DTOs\CreateAccountDTO;
use App\DTOs\UpdateAccountDTO;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountService implements AccountServiceInterface
{
    public function getPaginatedAccounts(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;

        return Account::query()
            ->with('currency:id,code,symbol')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('account_number', 'like', "%{$query}%")
                        ->orWhere('bank_name', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];

                $builder->where(function ($q) use ($statuses) {
                    foreach ($statuses as $statusValue) {
                        match ($statusValue) {
                            'active' => $q->orWhere('is_active', true),
                            'inactive' => $q->orWhere('is_active', false),
                            default => null,
                        };
                    }
                });
            })
            ->when($type, function ($builder) use ($type) {
                $types = is_array($type) ? $type : [$type];
                $builder->whereIn('type', $types);
            })
            ->withTrashed()
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createAccount(CreateAccountDTO $dto): Account
    {
        return Account::create([
            'name' => $dto->name,
            'account_number' => $dto->accountNumber,
            'bank_name' => $dto->bankName,
            'bank_branch' => $dto->bankBranch,
            'currency_id' => $dto->currencyId,
            'type' => $dto->type,
            'opening_balance' => $dto->openingBalance,
            'is_active' => $dto->isActive,
            'notes' => $dto->notes,
        ]);
    }

    public function updateAccount(Account $account, UpdateAccountDTO $dto): Account
    {
        $account->update([
            'name' => $dto->name,
            'account_number' => $dto->accountNumber,
            'bank_name' => $dto->bankName,
            'bank_branch' => $dto->bankBranch,
            'currency_id' => $dto->currencyId,
            'type' => $dto->type,
            'opening_balance' => $dto->openingBalance,
            'is_active' => $dto->isActive,
            'notes' => $dto->notes,
        ]);

        return $account->fresh();
    }

    public function deleteAccount(Account $account): void
    {
        $account->delete();
    }

    public function restoreAccount(int|string $id): Account
    {
        $account = Account::withTrashed()->findOrFail($id);
        $account->restore();

        return $account;
    }

    public function bulkDelete(array $ids): void
    {
        Account::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
