<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class CreateNewsDTO
{
    /**
     * @param  array<int, UploadedFile>  $gallery
     */
    public function __construct(
        public string $titleAr,
        public string $titleEn,
        public string $slug,
        public ?int $categoryId,
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
        public array $gallery = [],
    ) {}
}
