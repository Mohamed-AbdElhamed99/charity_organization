<?php

namespace App\Contracts\Services;

use App\DTOs\CreateNewsDTO;
use App\DTOs\UpdateNewsDTO;
use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NewsServiceInterface
{
    public function getPaginatedNews(array $filters): LengthAwarePaginator;

    public function createNews(CreateNewsDTO $dto): News;

    public function updateNews(News $news, UpdateNewsDTO $dto): News;

    public function deleteNews(News $news): void;

    public function restoreNews(int|string $id): News;

    public function bulkDelete(array $ids): void;
}
