<?php

namespace App\DTOs;

readonly class CreateRoleDTO
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}
}
