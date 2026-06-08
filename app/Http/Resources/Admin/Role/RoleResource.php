<?php

namespace App\Http\Resources\Admin\Role;

use App\Contracts\Services\RoleServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissions' => $this->permissions->pluck('name')->values(),
            'users_count' => $this->whenCounted('users'),
            'is_system' => app(RoleServiceInterface::class)
                ->isSystemRole($this->resource),
            'is_protected' => app(RoleServiceInterface::class)
                ->isProtectedFromEdit($this->resource),
        ];
    }
}
