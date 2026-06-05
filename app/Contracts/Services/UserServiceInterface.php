<?php

namespace App\Contracts\Services;

use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function getPaginatedUsers(array $filters): LengthAwarePaginator;

    public function createUser(CreateUserDTO $dto): User;

    public function updateUser(User $user, UpdateUserDTO $dto): User;

    public function deleteUser(User $user): void;

    public function restoreUser(int|string $id): User;

    public function bulkDelete(array $ids): void;
}
