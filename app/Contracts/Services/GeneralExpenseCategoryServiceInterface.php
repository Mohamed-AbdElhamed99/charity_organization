<?php

namespace App\Contracts\Services;

use App\DTOs\CreateGeneralExpenseCategoryDTO;
use App\DTOs\UpdateGeneralExpenseCategoryDTO;
use App\Models\GeneralExpenseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GeneralExpenseCategoryServiceInterface
{
    public function getPaginatedCategories(array $filters): LengthAwarePaginator;

    public function createCategory(CreateGeneralExpenseCategoryDTO $dto): GeneralExpenseCategory;

    public function updateCategory(GeneralExpenseCategory $category, UpdateGeneralExpenseCategoryDTO $dto): GeneralExpenseCategory;

    /**
     * @return 'deleted'|'deactivated'
     */
    public function deleteCategory(GeneralExpenseCategory $category): string;

    public function restoreCategory(int|string $id): GeneralExpenseCategory;

    /**
     * @return array{deleted: int, deactivated: int}
     */
    public function bulkDelete(array $ids): array;
}
