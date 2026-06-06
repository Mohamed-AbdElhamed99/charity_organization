<?php

namespace App\Http\Resources\Site;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
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

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'meta_title' => $this->meta_title ?: $this->title,
            'meta_description' => $this->meta_description ?: $this->excerpt,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'thumbnail' => $this->getFirstMediaUrl('thumbnail') ?: ($firstGalleryImage?->getUrl() ?? ''),
            'main_media' => $this->getFirstMediaUrl('main_media') ?: ($firstGalleryItem?->getUrl() ?? ''),
            'main_media_type' => $this->getFirstMedia('main_media')?->mime_type ?? $firstGalleryItem?->mime_type,
            'gallery' => $galleryMedia->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
            ])->values(),
            'published_at' => $this->published_at?->format('Y-m-d'),
            'video_url' => $this->video_url,
        ];
    }
}
