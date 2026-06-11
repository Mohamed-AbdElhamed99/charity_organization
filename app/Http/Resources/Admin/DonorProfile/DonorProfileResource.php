<?php

namespace App\Http\Resources\Admin\DonorProfile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'organization_name' => $this->organization_name,
            'address' => $this->address,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->name,
            'state_id' => $this->state_id,
            'state_name' => $this->state?->name,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'status' => $this->user->status?->value,
            ]),
        ];
    }
}
