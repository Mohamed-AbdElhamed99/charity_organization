<?php

namespace App\Services;

use App\Contracts\Services\ContactMessageServiceInterface;
use App\DTOs\CreateContactMessageDTO;
use App\Mail\ContactMessageConfirmationMailable;
use App\Mail\ContactMessageReceivedMailable;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class ContactMessageService implements ContactMessageServiceInterface
{
    public function getPaginatedMessages(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return ContactUs::query()
            ->with('reviewer:id,name')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('fullname', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('subject', 'like', "%{$query}%")
                        ->orWhere('message', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];

                $builder->where(function ($q) use ($statuses) {
                    foreach ($statuses as $statusValue) {
                        match ($statusValue) {
                            'reviewed' => $q->orWhere('is_reviewed', true),
                            'unreviewed' => $q->orWhere('is_reviewed', false),
                            default => null,
                        };
                    }
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createMessage(CreateContactMessageDTO $dto): ContactUs
    {
        $message = ContactUs::create([
            'fullname' => $dto->fullname,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'subject' => $dto->subject,
            'message' => $dto->message,
        ]);

        $this->dispatchNotifications($message);

        return $message;
    }

    public function markAsReviewed(ContactUs $message, User $reviewer, ?string $notes = null): ContactUs
    {
        $message->markReviewed($reviewer, $notes);

        return $message->fresh(['reviewer']);
    }

    public function deleteMessage(ContactUs $message): void
    {
        $message->delete();
    }

    public function bulkDelete(array $ids): void
    {
        ContactUs::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    private function dispatchNotifications(ContactUs $message): void
    {
        try {
            Mail::to(config('site.admin_notification_email'))
                ->queue(new ContactMessageReceivedMailable($message));

            Mail::to($message->email)
                ->queue(new ContactMessageConfirmationMailable($message));
        } catch (\Throwable) {
            // Persist even if mail fails.
        }
    }
}
