<?php

namespace App\Services;

use App\Contracts\Services\CampaignServiceInterface;
use App\DTOs\CreateCampaignDTO;
use App\DTOs\UpdateCampaignDTO;
use App\Models\Campaign;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CampaignService implements CampaignServiceInterface
{
    public function getPaginatedCampaigns(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $allowedSorts = ['created_at', 'start_date', 'budget', 'donation_target'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return Campaign::query()
            ->with(['category', 'media'])
            ->withCount(['expenses', 'donations'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('title_ar', 'like', "%{$query}%")
                        ->orWhere('title_en', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%");
                });
            })
            ->when($category, function ($builder) use ($category) {
                $categories = is_array($category) ? $category : [$category];
                $builder->whereIn('category_id', $categories);
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];
                $builder->whereIn('status', $statuses);
            })
            ->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createCampaign(CreateCampaignDTO $dto): Campaign
    {
        $campaign = Campaign::create([
            'category_id' => $dto->categoryId,
            'slug' => $dto->slug,
            'title_ar' => $dto->titleAr,
            'title_en' => $dto->titleEn,
            'excerpt_ar' => $dto->excerptAr,
            'excerpt_en' => $dto->excerptEn,
            'description_ar' => $dto->descriptionAr,
            'description_en' => $dto->descriptionEn,
            'start_date' => $dto->startDate,
            'end_date' => $dto->endDate,
            'address' => $dto->address,
            'country_id' => $dto->countryId,
            'state_id' => $dto->stateId,
            'lat' => $dto->lat,
            'lng' => $dto->lng,
            'budget' => $dto->budget,
            'donation_target' => $dto->donationTarget,
            'status' => $dto->status,
            'is_public' => $dto->isPublic,
            'open_donation_form' => $dto->openDonationForm,
            'is_repeated' => $dto->isRepeated,
            'repeat_until' => $dto->repeatUntil,
            'meta_title_ar' => $dto->metaTitleAr,
            'meta_title_en' => $dto->metaTitleEn,
            'meta_description_ar' => $dto->metaDescriptionAr,
            'meta_description_en' => $dto->metaDescriptionEn,
            'created_by' => $dto->createdBy,
        ]);

        $this->syncMedia($campaign, $dto->cover, $dto->gallery);

        return $campaign->load(['category', 'media']);
    }

    public function updateCampaign(Campaign $campaign, UpdateCampaignDTO $dto): Campaign
    {
        $campaign->fill([
            'category_id' => $dto->categoryId,
            'slug' => $dto->slug,
            'title_ar' => $dto->titleAr,
            'title_en' => $dto->titleEn,
            'excerpt_ar' => $dto->excerptAr,
            'excerpt_en' => $dto->excerptEn,
            'description_ar' => $dto->descriptionAr,
            'description_en' => $dto->descriptionEn,
            'start_date' => $dto->startDate,
            'end_date' => $dto->endDate,
            'address' => $dto->address,
            'country_id' => $dto->countryId,
            'state_id' => $dto->stateId,
            'lat' => $dto->lat,
            'lng' => $dto->lng,
            'budget' => $dto->budget,
            'donation_target' => $dto->donationTarget,
            'status' => $dto->status,
            'is_public' => $dto->isPublic,
            'open_donation_form' => $dto->openDonationForm,
            'is_repeated' => $dto->isRepeated,
            'repeat_until' => $dto->repeatUntil,
            'meta_title_ar' => $dto->metaTitleAr,
            'meta_title_en' => $dto->metaTitleEn,
            'meta_description_ar' => $dto->metaDescriptionAr,
            'meta_description_en' => $dto->metaDescriptionEn,
        ]);
        $campaign->save();

        if ($dto->cover) {
            $campaign->clearMediaCollection('cover');
            $campaign->addMedia($dto->cover)->toMediaCollection('cover');
        }

        if ($dto->removedGalleryIds !== null && $dto->removedGalleryIds !== []) {
            Media::query()
                ->where('model_type', Campaign::class)
                ->where('model_id', $campaign->id)
                ->where('collection_name', 'gallery')
                ->whereIn('id', $dto->removedGalleryIds)
                ->each(fn (Media $media) => $media->delete());
        }

        if ($dto->gallery !== null && $dto->gallery !== []) {
            foreach ($dto->gallery as $file) {
                $campaign->addMedia($file)->toMediaCollection('gallery');
            }
        }

        return $campaign->load(['category', 'media']);
    }

    public function deleteCampaign(Campaign $campaign): void
    {
        if ($campaign->expenses()->exists() || $campaign->donations()->exists()) {
            throw new DomainException(__('Cannot delete a campaign with recorded expenses or donations.'));
        }

        $campaign->delete();
    }

    /**
     * @param  array<int, UploadedFile>  $gallery
     */
    private function syncMedia(Campaign $campaign, ?UploadedFile $cover, array $gallery): void
    {
        if ($cover) {
            $campaign->addMedia($cover)->toMediaCollection('cover');
        }

        foreach ($gallery as $file) {
            $campaign->addMedia($file)->toMediaCollection('gallery');
        }
    }
}
