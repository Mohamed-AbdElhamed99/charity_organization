<?php

namespace App\Http\Resources\Admin\Meeting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingAttendeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'title' => $this->title,
            'organization' => $this->organization,
            'email' => $this->email,
            'phone' => $this->phone,
            'attendance_status' => $this->attendance_status?->value,
            'attendance_status_label' => $this->attendance_status?->label(),
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'signature_present' => $this->signature_present,
            'notes' => $this->notes,
        ];
    }
}
