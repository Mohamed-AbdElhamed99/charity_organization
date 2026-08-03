<?php

namespace App\Services;

use App\Contracts\Services\TransactionTypeServiceInterface;
use App\DTOs\CreateTransactionTypeDTO;
use App\DTOs\UpdateTransactionTypeDTO;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class TransactionTypeService implements TransactionTypeServiceInterface
{
    public function getPaginatedTransactionTypes(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return TransactionType::query()
            ->withCount('transactions')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
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
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createTransactionType(CreateTransactionTypeDTO $dto): TransactionType
    {
        return TransactionType::create([
            'name' => $dto->name,
            'code' => $dto->code,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
            'is_system' => false,
        ]);
    }

    public function updateTransactionType(TransactionType $transactionType, UpdateTransactionTypeDTO $dto): TransactionType
    {
        $attributes = [
            'name' => $dto->name,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
        ];

        if (! $transactionType->is_system) {
            $attributes['code'] = $dto->code;
        }

        $transactionType->update($attributes);

        return $transactionType->fresh();
    }

    public function deleteTransactionType(TransactionType $transactionType): string
    {
        if ($transactionType->is_system) {
            throw new InvalidArgumentException('System transaction types cannot be deleted.');
        }

        if ($this->isReferenced($transactionType)) {
            $transactionType->update(['is_active' => false]);

            return 'deactivated';
        }

        $transactionType->delete();

        return 'deleted';
    }

    public function restoreTransactionType(int|string $id): TransactionType
    {
        $transactionType = TransactionType::withTrashed()->findOrFail($id);
        $transactionType->restore();
        $transactionType->update(['is_active' => true]);

        return $transactionType;
    }

    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $deactivated = 0;
        $blocked = 0;

        TransactionType::query()
            ->whereIn('id', $ids)
            ->each(function (TransactionType $transactionType) use (&$deleted, &$deactivated, &$blocked) {
                if ($transactionType->is_system) {
                    $blocked++;

                    return;
                }

                if ($this->deleteTransactionType($transactionType) === 'deactivated') {
                    $deactivated++;
                } else {
                    $deleted++;
                }
            });

        return [
            'deleted' => $deleted,
            'deactivated' => $deactivated,
            'blocked' => $blocked,
        ];
    }

    private function isReferenced(TransactionType $transactionType): bool
    {
        return Transaction::query()
            ->where('transaction_type_id', $transactionType->id)
            ->exists();
    }
}
