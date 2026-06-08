<?php

namespace App\Contracts\Services;

use App\DTOs\CreateCampaignCategoryDTO;
use App\DTOs\UpdateCampaignCategoryDTO;
use App\Models\CampaignCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignCategoryServiceInterface
{
    public function getPaginatedCampaignCategories(array $filters): LengthAwarePaginator;

    public function createCampaignCategory(CreateCampaignCategoryDTO $dto): CampaignCategory;

    public function updateCampaignCategory(CampaignCategory $campaignCategory, UpdateCampaignCategoryDTO $dto): CampaignCategory;

    public function deleteCampaignCategory(CampaignCategory $campaignCategory): void;

    public function restoreCampaignCategory(int|string $id): CampaignCategory;

    public function bulkDelete(array $ids): void;
}
