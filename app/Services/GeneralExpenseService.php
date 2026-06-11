<?php

namespace App\Services;

use App\Contracts\Services\GeneralExpenseServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateGeneralExpenseDTO;
use App\DTOs\UpdateGeneralExpenseDTO;
use App\Models\GeneralExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GeneralExpenseService implements GeneralExpenseServiceInterface
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function getPaginatedExpenses(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return GeneralExpense::query()
            ->with([
                'category',
                'creator',
                'transaction.account',
                'transaction.paymentMethod',
                'transaction.currency',
            ])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('vendor_name', 'like', "%{$query}%")
                        ->orWhereHas('transaction', function ($tq) use ($query) {
                            $tq->where('description', 'like', "%{$query}%");
                        });
                });
            })
            ->when($categoryId, fn ($builder) => $builder->where('category_id', (int) $categoryId))
            ->when($dateFrom && $dateTo, fn ($builder) => $builder->inDateRange($dateFrom, $dateTo))
            ->when($dateFrom && ! $dateTo, fn ($builder) => $builder->where('expense_date', '>=', $dateFrom))
            ->when($dateTo && ! $dateFrom, fn ($builder) => $builder->where('expense_date', '<=', $dateTo))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createExpense(CreateGeneralExpenseDTO $dto): GeneralExpense
    {
        $transaction = $this->transactionService->createForGeneralExpense([
            'account_id' => $dto->accountId,
            'category_id' => $dto->categoryId,
            'payment_method_id' => $dto->paymentMethodId,
            'name' => $dto->name,
            'amount' => $dto->amount,
            'expense_date' => $dto->expenseDate,
            'vendor_name' => $dto->vendorName,
            'is_recurring' => $dto->isRecurring,
            'description' => $dto->description ?? $dto->name,
            'notes' => $dto->notes,
            'reference_number' => $dto->referenceNumber,
        ]);

        return $transaction->generalExpense;
    }

    public function updateExpense(GeneralExpense $expense, UpdateGeneralExpenseDTO $dto): GeneralExpense
    {
        $expense->update([
            'category_id' => $dto->categoryId,
            'name' => $dto->name,
            'vendor_name' => $dto->vendorName,
            'is_recurring' => $dto->isRecurring,
            'notes' => $dto->notes,
        ]);

        return $expense->fresh([
            'category',
            'creator',
            'transaction.account',
            'transaction.paymentMethod',
            'transaction.currency',
        ]);
    }

    public function reverseExpense(GeneralExpense $expense, int $userId): void
    {
        $this->transactionService->reverseTransaction($expense->transaction, $userId);
    }
}
