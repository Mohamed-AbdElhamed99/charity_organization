<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\UserServiceInterface;
use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\BulkDestroyUserRequest;
use App\Http\Requests\Admin\Users\RestoreUserRequest;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Http\Resources\Admin\User\UserResource;
use App\Http\Resources\Admin\User\UserShowResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'role', 'status', 'page', 'per_page']);

        return Inertia::render('admin/users/users-index', [
            'users' => fn () => $this->resolvePaginatedUsers($filters),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'search' => $filters,
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['roles', 'permissions', 'country', 'state', 'media']);

        return Inertia::render('admin/users/users-show', [
            'user' => (new UserShowResource($user))->resolve(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->userService->createUser(new CreateUserDTO(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            phone: $validated['phone'] ?? null,
            status: $validated['status'],
            role: $validated['role'],
            avatar: $request->file('avatar'),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created successfully.')]);

        return back();
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $this->userService->updateUser($user, new UpdateUserDTO(
            name: $validated['name'] ?? null,
            email: $validated['email'] ?? null,
            password: $validated['password'] ?? null,
            phone: array_key_exists('phone', $validated) ? $validated['phone'] : null,
            status: $validated['status'] ?? null,
            role: $validated['role'] ?? null,
            avatar: $request->file('avatar'),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated successfully.')]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {

        $this->userService->deleteUser($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyUserRequest $request): RedirectResponse
    {
        $this->userService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Users deleted successfully.')]);

        return back();
    }

    public function restore(RestoreUserRequest $request, int|string $id): RedirectResponse
    {
        $this->userService->restoreUser($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User restored successfully.')]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function resolvePaginatedUsers(array $filters): array
    {
        $paginator = $this->userService->getPaginatedUsers($filters);

        $users = $paginator->toArray();
        $users['data'] = UserResource::collection($paginator->items())->resolve();

        return $users;
    }
}
