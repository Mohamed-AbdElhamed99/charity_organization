<?php

namespace App\Http\Resources\Admin\News;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'subtitle_ar' => $this->subtitle_ar,
            'subtitle_en' => $this->subtitle_en,
            'excerpt_ar' => $this->excerpt_ar,
            'excerpt_en' => $this->excerpt_en,
            'body_ar' => $this->body_ar,
            'body_en' => $this->body_en,
            'video_url' => $this->video_url,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name_en,
            'is_active' => $this->is_active,
            'is_private' => $this->is_private,
            'published_at' => $this->published_at?->format('Y-m-d'),
            'meta_title_ar' => $this->meta_title_ar,
            'meta_title_en' => $this->meta_title_en,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_description_en' => $this->meta_description_en,
            'thumbnail' => $this->getFirstMediaUrl('thumbnail'),
            'main_media' => $this->getFirstMediaUrl('main_media'),
            'gallery' => $this->getMedia('gallery')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
            ])->values(),
            'created_at' => $this->created_at?->toDateString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
