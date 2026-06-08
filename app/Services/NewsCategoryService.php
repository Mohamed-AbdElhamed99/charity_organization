<?php

namespace App\Services;

use App\Contracts\Services\NewsCategoryServiceInterface;
use App\DTOs\CreateNewsCategoryDTO;
use App\DTOs\UpdateNewsCategoryDTO;
use App\Models\NewsCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NewsCategoryService implements NewsCategoryServiceInterface
{
    public function getPaginatedNewsCategories(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return NewsCategory::query()
            ->withCount('news')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name_ar', 'like', "%{$query}%")
                        ->orWhere('name_en', 'like', "%{$query}%");
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
            ->orderBy('name_en')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createNewsCategory(CreateNewsCategoryDTO $dto): NewsCategory
    {
        return NewsCategory::create([
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'is_active' => $dto->isActive,
        ]);
    }

    public function updateNewsCategory(NewsCategory $newsCategory, UpdateNewsCategoryDTO $dto): NewsCategory
    {
        $newsCategory->update([
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'is_active' => $dto->isActive,
        ]);

        return $newsCategory->fresh();
    }

    public function deleteNewsCategory(NewsCategory $newsCategory): void
    {
        $newsCategory->delete();
    }

    public function restoreNewsCategory(int|string $id): NewsCategory
    {
        $newsCategory = NewsCategory::withTrashed()->findOrFail($id);
        $newsCategory->restore();

        return $newsCategory;
    }

    public function bulkDelete(array $ids): void
    {
        NewsCategory::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
