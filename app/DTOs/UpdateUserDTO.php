<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $phone = null,
        public ?string $status = null,
        public ?string $role = null,
        public ?UploadedFile $avatar = null,
    ) {}
}
