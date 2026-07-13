<?php

namespace App\Http\Resources\Admin\Meeting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
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
            'duration' => $this->duration,
            'location' => $this->location,
            'location_type' => $this->location_type?->value,
            'location_type_label' => $this->location_type?->label(),
            'meeting_link' => $this->meeting_link,
            'agenda' => $this->agenda,
            'description' => $this->description,
            'quorum_required' => $this->quorum_required,
            'quorum_met' => $this->quorum_met,
            'chairperson' => $this->chairperson,
            'secretary' => $this->secretary,
            'notes' => $this->notes,
            'attended_count' => $this->attended_count,
            'attendees_count' => $this->whenCounted('attendees'),
            'decisions_count' => $this->whenCounted('decisions'),
            'minutes' => $this->whenLoaded('minutes', fn () => $this->minutes
                ? (new MeetingMinutesResource($this->minutes))->resolve()
                : null),
            'decisions' => MeetingDecisionResource::collection($this->whenLoaded('decisions')),
            'attendees' => MeetingAttendeeResource::collection($this->whenLoaded('attendees')),
            'attachments' => MeetingAttachmentResource::collection($this->whenLoaded('attachments')),
            'campaigns' => $this->whenLoaded('campaigns', fn () => $this->campaigns->map(fn ($campaign) => [
                'id' => $campaign->id,
                'title_en' => $campaign->title_en,
                'title_ar' => $campaign->title_ar,
                'slug' => $campaign->slug,
                'relationship_type' => $campaign->pivot?->relationship_type,
                'notes' => $campaign->pivot?->notes,
            ])->values()),
            'campaign_ids' => $this->whenLoaded('campaigns', fn () => $this->campaigns->pluck('id')->values()),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null),
            'updated_by' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
