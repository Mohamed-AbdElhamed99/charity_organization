<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class UpdateNewsDTO
{
    /**
     * @param  array<int, UploadedFile>|null  $gallery
     * @param  array<int, int>|null  $removedGalleryIds
     */
    public function __construct(
        public ?int $categoryId,
        public string $slug,
        public string $titleAr,
        public string $titleEn,
        public ?string $subtitleAr,
        public ?string $subtitleEn,
        public ?string $excerptAr,
        public ?string $excerptEn,
        public ?string $bodyAr,
        public ?string $bodyEn,
        public ?string $videoUrl,
        public ?string $publishedAt,
        public bool $isActive,
        public bool $isPrivate,
        public ?string $metaTitleAr,
        public ?string $metaTitleEn,
        public ?string $metaDescriptionAr,
        public ?string $metaDescriptionEn,
        public ?UploadedFile $thumbnail = null,
        public ?UploadedFile $mainMedia = null,
        public ?array $gallery = null,
        public ?array $removedGalleryIds = null,
    ) {}
}
