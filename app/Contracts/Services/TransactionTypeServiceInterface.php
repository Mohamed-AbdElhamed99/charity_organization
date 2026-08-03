<?php

namespace App\Contracts\Services;

use App\DTOs\CreateTransactionTypeDTO;
use App\DTOs\UpdateTransactionTypeDTO;
use App\Models\TransactionType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionTypeServiceInterface
{
    public function getPaginatedTransactionTypes(array $filters): LengthAwarePaginator;

    public function createTransactionType(CreateTransactionTypeDTO $dto): TransactionType;

    public function updateTransactionType(TransactionType $transactionType, UpdateTransactionTypeDTO $dto): TransactionType;

    /**
     * @return 'deleted'|'deactivated'
     */
    public function deleteTransactionType(TransactionType $transactionType): string;

    public function restoreTransactionType(int|string $id): TransactionType;

    /**
     * @return array{deleted: int, deactivated: int, blocked: int}
     */
    public function bulkDelete(array $ids): array;
}
