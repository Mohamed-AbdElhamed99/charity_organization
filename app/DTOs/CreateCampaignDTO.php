<?php

namespace App\DTOs;

use App\Enums\CampaignRecurrence;
use App\Enums\CampaignStatus;
use Illuminate\Http\UploadedFile;

readonly class CreateCampaignDTO
{
    /**
     * @param  array<int, UploadedFile>  $gallery
     * @param  array<int, int>  $meetingIds
     */
    public function __construct(
        public string $slug,
        public string $titleAr,
        public string $titleEn,
        public ?int $categoryId,
        public ?string $excerptAr,
        public ?string $excerptEn,
        public ?string $descriptionAr,
        public ?string $descriptionEn,
        public ?string $startDate,
        public ?string $endDate,
        public ?string $address,
        public ?int $countryId,
        public ?int $stateId,
        public ?float $lat,
        public ?float $lng,
        public float $budget,
        public ?float $donationTarget,
        public CampaignStatus $status,
        public bool $isPublic,
        public bool $openDonationForm,
        public CampaignRecurrence $isRepeated,
        public ?string $repeatUntil,
        public ?string $metaTitleAr,
        public ?string $metaTitleEn,
        public ?string $metaDescriptionAr,
        public ?string $metaDescriptionEn,
        public int $createdBy,
        public ?UploadedFile $cover = null,
        public array $gallery = [],
        public array $meetingIds = [],
    ) {}
}
