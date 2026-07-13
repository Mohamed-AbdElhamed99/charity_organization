<?php

namespace App\Http\Resources\Admin\Meeting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingDecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'decision_number' => $this->decision_number,
            'title' => $this->title,
            'description' => $this->description,
            'decision_type' => $this->decision_type?->value,
            'decision_type_label' => $this->decision_type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'completion_date' => $this->completion_date?->format('Y-m-d'),
            'completion_notes' => $this->completion_notes,
            'sort_order' => $this->sort_order,
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
