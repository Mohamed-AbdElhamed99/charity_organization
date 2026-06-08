<?php

namespace App\Contracts\Services;

use App\DTOs\CreateNewsCategoryDTO;
use App\DTOs\UpdateNewsCategoryDTO;
use App\Models\NewsCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NewsCategoryServiceInterface
{
    public function getPaginatedNewsCategories(array $filters): LengthAwarePaginator;

    public function createNewsCategory(CreateNewsCategoryDTO $dto): NewsCategory;

    public function updateNewsCategory(NewsCategory $newsCategory, UpdateNewsCategoryDTO $dto): NewsCategory;

    public function deleteNewsCategory(NewsCategory $newsCategory): void;

    public function restoreNewsCategory(int|string $id): NewsCategory;

    public function bulkDelete(array $ids): void;
}
