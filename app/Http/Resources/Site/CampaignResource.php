<?php

namespace App\Http\Resources\Site;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $galleryMedia = $this->getMedia('gallery');
        $firstGalleryItem = $galleryMedia->first();
        $firstGalleryImage = $galleryMedia->first(
            fn ($media) => str_starts_with($media->mime_type, 'image/')
        );
        $coverUrl = $this->getFirstMediaUrl('cover');
        $coverMedia = $this->getFirstMedia('cover');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'meta_title' => $this->meta_title ?: $this->title,
            'meta_description' => $this->meta_description ?: $this->excerpt,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'thumbnail' => $coverUrl ?: ($firstGalleryImage?->getUrl() ?? ''),
            'main_media' => $coverUrl ?: ($firstGalleryItem?->getUrl() ?? ''),
            'main_media_type' => $coverMedia?->mime_type ?? $firstGalleryItem?->mime_type,
            'gallery' => $galleryMedia->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
            ])->values(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'published_at' => $this->start_date?->format('Y-m-d'),
            'goal_amount_cents' => $this->donation_target
                ? Money::decimalToCents($this->donation_target)
                : null,
            'collected_amount_cents' => (int) ($this->collected_amount ?? 0),
            'open_donation_form' => (bool) $this->open_donation_form,
            'status' => $this->status?->value,
        ];
    }
}
