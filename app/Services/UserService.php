<?php

namespace App\Services;

use App\Contracts\Services\UserServiceInterface;
use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function getPaginatedUsers(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $role = $filters['role'] ?? null;
        $status = $filters['status'] ?? null;

        return User::query()
            ->with('roles', 'media')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->when($role, function ($builder) use ($role) {
                $roles = is_array($role) ? $role : [$role];
                $builder->whereHas('roles', fn ($q) => $q->whereIn('name', $roles));
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];
                $builder->whereIn('status', $statuses);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createUser(CreateUserDTO $dto): User
    {
        $user = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
            'phone' => $dto->phone,
            'status' => UserStatus::from($dto->status),
        ]);

        $user->assignRole($dto->role);

        if ($dto->avatar) {
            $user
                ->addMedia($dto->avatar)
                ->toMediaCollection('avatars');
        }

        return $user->load('roles', 'media');
    }

    public function updateUser(User $user, UpdateUserDTO $dto): User
    {
        $attributes = array_filter([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'status' => $dto->status ? UserStatus::from($dto->status) : null,
        ], fn ($value) => $value !== null);

        if ($dto->password) {
            $attributes['password'] = $dto->password;
        }

        $user->fill($attributes);
        $user->save();

        if ($dto->role) {
            $user->syncRoles([$dto->role]);
        }

        if ($dto->avatar) {
            $user->clearMediaCollection('avatars');
            $user
                ->addMedia($dto->avatar)
                ->toMediaCollection('avatars');
        }

        return $user->load('roles', 'media');
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function restoreUser(int|string $id): User
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return $user->load('roles', 'media');
    }

    public function bulkDelete(array $ids): void
    {
        $authId = Auth::id();

        if ($authId && in_array($authId, $ids, true)) {
            throw ValidationException::withMessages([
                'ids' => __('You cannot delete your own account.'),
            ]);
        }

        User::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}