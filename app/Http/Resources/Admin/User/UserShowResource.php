<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'national_id' => $this->national_id,
            'job' => $this->job,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'bio' => $this->bio,
            'gender' => $this->gender?->value,
            'address' => $this->address,
            'country_name' => $this->country?->name,
            'state_name' => $this->state?->name,
            'avatar' => $this->getFirstMediaUrl('avatars'),
            'created_at' => $this->created_at?->toDateString(),
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'roles' => $this->getRoleNames()->values()->all(),
            // Role assignment UI can be added here later (attach/detach roles).
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
