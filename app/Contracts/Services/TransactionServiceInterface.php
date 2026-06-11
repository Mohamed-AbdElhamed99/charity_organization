<?php

namespace App\Contracts\Services;

use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedTransactions(array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Transaction>
     */
    public function getFilteredTransactions(array $filters): Collection;

    public function createTransaction(CreateTransactionDTO $dto): Transaction;

    public function updateTransaction(Transaction $transaction, UpdateTransactionDTO $dto): Transaction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForExpense(array $data): Transaction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForTransfer(array $data): Transaction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForGeneralExpense(array $data): Transaction;

    public function reverseTransaction(Transaction $transaction, int $userId): Transaction;
}
