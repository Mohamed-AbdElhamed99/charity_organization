<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\RoleServiceInterface;
use App\DTOs\CreateRoleDTO;
use App\DTOs\UpdateRoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Admin\Role\RoleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'page', 'per_page']);
        $paginator = $this->roleService->getPaginatedRoles($filters);

        $roles = $paginator->toArray();
        $roles['data'] = RoleResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/roles/roles-index', [
            'roles' => $roles,
            'permissions' => $this->roleService->getAllPermissionNames(),
            'permissionGroups' => $this->roleService->getGroupedPermissions(),
            'search' => $filters,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->roleService->createRole(new CreateRoleDTO(
            name: $validated['name'],
            permissions: $validated['permissions'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created successfully.')]);

        return back();
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $this->roleService->updateRole($role, new UpdateRoleDTO(
            name: $validated['name'],
            permissions: $validated['permissions'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated successfully.')]);

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless(request()->user()?->can('manage_roles'), 403);

        $this->roleService->deleteRole($role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted successfully.')]);

        return back();
    }
}
