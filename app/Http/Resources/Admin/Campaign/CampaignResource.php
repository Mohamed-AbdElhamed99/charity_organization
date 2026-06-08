<?php

namespace App\Http\Resources\Admin\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'excerpt_ar' => $this->excerpt_ar,
            'excerpt_en' => $this->excerpt_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name_en,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'address' => $this->address,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'budget' => (float) $this->budget,
            'donation_target' => $this->donation_target !== null ? (float) $this->donation_target : null,
            'status' => $this->status?->value,
            'is_public' => $this->is_public,
            'open_donation_form' => $this->open_donation_form,
            'is_repeated' => $this->is_repeated?->value,
            'repeat_until' => $this->repeat_until?->format('Y-m-d'),
            'meta_title_ar' => $this->meta_title_ar,
            'meta_title_en' => $this->meta_title_en,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_description_en' => $this->meta_description_en,
            'cover_url' => $this->getFirstMediaUrl('cover'),
            'gallery' => $this->getMedia('gallery')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
            ])->values(),
            'expenses_count' => $this->whenCounted('expenses'),
            'donations_count' => $this->whenCounted('donations'),
            'total_donated' => $this->when(
                $request->routeIs('admin.campaigns.show'),
                fn () => (float) $this->total_donated
            ),
            'total_expenses' => $this->when(
                $request->routeIs('admin.campaigns.show'),
                fn () => (float) $this->total_expenses
            ),
            'remaining_budget' => $this->when(
                $request->routeIs('admin.campaigns.show'),
                fn () => (float) $this->remaining_budget
            ),
            'donation_progress' => $this->when(
                $request->routeIs('admin.campaigns.show'),
                fn () => $this->donation_progress
            ),
            'created_at' => $this->created_at?->toDateString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
