<?php

namespace App\Services;

use App\Contracts\Services\GeneralExpenseCategoryServiceInterface;
use App\DTOs\CreateGeneralExpenseCategoryDTO;
use App\DTOs\UpdateGeneralExpenseCategoryDTO;
use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GeneralExpenseCategoryService implements GeneralExpenseCategoryServiceInterface
{
    public function getPaginatedCategories(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return GeneralExpenseCategory::query()
            ->withCount('expenses')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
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
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createCategory(CreateGeneralExpenseCategoryDTO $dto): GeneralExpenseCategory
    {
        return GeneralExpenseCategory::create([
            'name' => $dto->name,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
        ]);
    }

    public function updateCategory(GeneralExpenseCategory $category, UpdateGeneralExpenseCategoryDTO $dto): GeneralExpenseCategory
    {
        $category->update([
            'name' => $dto->name,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
        ]);

        return $category->fresh();
    }

    public function deleteCategory(GeneralExpenseCategory $category): string
    {
        if ($this->isReferenced($category)) {
            $category->update(['is_active' => false]);

            return 'deactivated';
        }

        $category->delete();

        return 'deleted';
    }

    public function restoreCategory(int|string $id): GeneralExpenseCategory
    {
        $category = GeneralExpenseCategory::withTrashed()->findOrFail($id);
        $category->restore();
        $category->update(['is_active' => true]);

        return $category;
    }

    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $deactivated = 0;

        GeneralExpenseCategory::query()
            ->whereIn('id', $ids)
            ->each(function (GeneralExpenseCategory $category) use (&$deleted, &$deactivated) {
                if ($this->deleteCategory($category) === 'deactivated') {
                    $deactivated++;
                } else {
                    $deleted++;
                }
            });

        return ['deleted' => $deleted, 'deactivated' => $deactivated];
    }

    private function isReferenced(GeneralExpenseCategory $category): bool
    {
        return GeneralExpense::query()
            ->where('category_id', $category->id)
            ->exists();
    }
}
