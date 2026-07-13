<?php

namespace App\Http\Resources\Admin\Meeting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'meeting_number' => $this->meeting_number,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'meeting_date' => $this->meeting_date?->format('Y-m-d'),
            'formatted_date' => $this->formatted_date,
            'start_time' => $this->start_time ? substr((string) $this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr((string) $this->end_time, 0, 5) : null,
            'location' => $this->location,
            'location_type' => $this->location_type?->value,
            'attendees_count' => $this->whenCounted('attendees'),
            'decisions_count' => $this->whenCounted('decisions'),
            'campaigns' => $this->whenLoaded('campaigns', fn () => $this->campaigns->map(fn ($campaign) => [
                'id' => $campaign->id,
                'title_en' => $campaign->title_en,
                'slug' => $campaign->slug,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
