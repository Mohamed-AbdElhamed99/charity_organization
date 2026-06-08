<?php

namespace App\Contracts\Services;

use App\DTOs\CreateRoleDTO;
use App\DTOs\UpdateRoleDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleServiceInterface
{
    public function getPaginatedRoles(array $filters): LengthAwarePaginator;

    /**
     * @return array<string, array<int, string>>
     */
    public function getGroupedPermissions(): array;

    /**
     * @return array<int, string>
     */
    public function getAllPermissionNames(): array;

    public function createRole(CreateRoleDTO $dto): Role;

    public function updateRole(Role $role, UpdateRoleDTO $dto): Role;

    public function deleteRole(Role $role): void;

    public function isSystemRole(Role $role): bool;

    public function isProtectedFromEdit(Role $role): bool;
}
