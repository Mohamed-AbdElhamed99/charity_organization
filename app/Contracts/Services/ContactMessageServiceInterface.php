<?php

namespace App\Contracts\Services;

use App\DTOs\CreateContactMessageDTO;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContactMessageServiceInterface
{
    public function getPaginatedMessages(array $filters): LengthAwarePaginator;

    public function createMessage(CreateContactMessageDTO $dto): ContactUs;

    public function markAsReviewed(ContactUs $message, User $reviewer, ?string $notes = null): ContactUs;

    public function deleteMessage(ContactUs $message): void;

    public function bulkDelete(array $ids): void;
}
