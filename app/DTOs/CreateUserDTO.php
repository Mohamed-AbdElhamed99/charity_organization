<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public string $status,
        public string $role,
        public ?UploadedFile $avatar = null,
    ) {}
}
