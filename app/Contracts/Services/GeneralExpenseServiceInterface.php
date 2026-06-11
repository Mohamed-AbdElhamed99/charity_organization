<?php

namespace App\Contracts\Services;

use App\DTOs\CreateGeneralExpenseDTO;
use App\DTOs\UpdateGeneralExpenseDTO;
use App\Models\GeneralExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GeneralExpenseServiceInterface
{
    public function getPaginatedExpenses(array $filters): LengthAwarePaginator;

    public function createExpense(CreateGeneralExpenseDTO $dto): GeneralExpense;

    public function updateExpense(GeneralExpense $expense, UpdateGeneralExpenseDTO $dto): GeneralExpense;

    public function reverseExpense(GeneralExpense $expense, int $userId): void;
}
