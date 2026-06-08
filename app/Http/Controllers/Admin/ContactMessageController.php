<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\ContactMessageServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactMessage\BulkDestroyContactMessageRequest;
use App\Http\Requests\Admin\ContactMessage\DestroyContactMessageRequest;
use App\Http\Requests\Admin\ContactMessage\MarkContactMessageReviewedRequest;
use App\Http\Resources\Admin\ContactMessage\ContactMessageResource;
use App\Models\ContactUs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function __construct(
        private readonly ContactMessageServiceInterface $contactMessageService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->contactMessageService->getPaginatedMessages($filters);

        $messages = $paginator->toArray();
        $messages['data'] = ContactMessageResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/contact-messages/contact-messages-index', [
            'messages' => $messages,
            'search' => $filters,
        ]);
    }

    public function show(ContactUs $contactMessage): Response
    {
        $contactMessage->load('reviewer:id,name');

        return Inertia::render('admin/contact-messages/contact-messages-show', [
            'message' => (new ContactMessageResource($contactMessage))->resolve(),
        ]);
    }

    public function markReviewed(MarkContactMessageReviewedRequest $request, ContactUs $contactMessage): RedirectResponse
    {
        $this->contactMessageService->markAsReviewed(
            $contactMessage,
            $request->user(),
            $request->validated('review_notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Message marked as reviewed.')]);

        return back();
    }

    public function destroy(DestroyContactMessageRequest $request, ContactUs $contactMessage): RedirectResponse
    {
        $this->contactMessageService->deleteMessage($contactMessage);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Message deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyContactMessageRequest $request): RedirectResponse
    {
        $this->contactMessageService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Messages deleted successfully.')]);

        return back();
    }
}
