<?php

namespace App\Services;

use App\Contracts\Services\AccountServiceInterface;
use App\DTOs\CreateAccountDTO;
use App\DTOs\UpdateAccountDTO;
use App\Models\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountService implements AccountServiceInterface
{
    public function getPaginatedAccounts(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;

        return BankAccount::query()
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

    public function createAccount(CreateAccountDTO $dto): BankAccount
    {
        return BankAccount::create([
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

    public function updateAccount(BankAccount $account, UpdateAccountDTO $dto): BankAccount
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

    public function deleteAccount(BankAccount $account): void
    {
        $account->delete();
    }

    public function restoreAccount(int|string $id): BankAccount
    {
        $account = BankAccount::withTrashed()->findOrFail($id);
        $account->restore();

        return $account;
    }

    public function bulkDelete(array $ids): void
    {
        BankAccount::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
