<?php

namespace App\Http\Resources\Admin\DonorProfile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorProfileListResource extends JsonResource
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
            'user_name' => $this->user?->name,
            'user_email' => $this->user?->email,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'organization_name' => $this->organization_name,
            'country_name' => $this->country?->name,
            'state_name' => $this->state?->name,
            'created_at' => $this->created_at?->toDateString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
