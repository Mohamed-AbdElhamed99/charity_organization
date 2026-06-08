<?php

namespace App\Services;

use App\Contracts\Services\CampaignCategoryServiceInterface;
use App\DTOs\CreateCampaignCategoryDTO;
use App\DTOs\UpdateCampaignCategoryDTO;
use App\Models\CampaignCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignCategoryService implements CampaignCategoryServiceInterface
{
    public function getPaginatedCampaignCategories(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return CampaignCategory::query()
            ->withCount('campaigns')
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

    public function createCampaignCategory(CreateCampaignCategoryDTO $dto): CampaignCategory
    {
        return CampaignCategory::create([
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
        ]);
    }

    public function updateCampaignCategory(CampaignCategory $campaignCategory, UpdateCampaignCategoryDTO $dto): CampaignCategory
    {
        $campaignCategory->update([
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
        ]);

        return $campaignCategory->fresh();
    }

    public function deleteCampaignCategory(CampaignCategory $campaignCategory): void
    {
        $campaignCategory->delete();
    }

    public function restoreCampaignCategory(int|string $id): CampaignCategory
    {
        $campaignCategory = CampaignCategory::withTrashed()->findOrFail($id);
        $campaignCategory->restore();

        return $campaignCategory;
    }

    public function bulkDelete(array $ids): void
    {
        CampaignCategory::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
