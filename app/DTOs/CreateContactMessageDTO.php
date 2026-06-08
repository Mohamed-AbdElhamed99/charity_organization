<?php

namespace App\DTOs;

readonly class CreateContactMessageDTO
{
    public function __construct(
        public string $fullname,
        public string $email,
        public ?string $phone,
        public string $subject,
        public string $message,
    ) {}
}
