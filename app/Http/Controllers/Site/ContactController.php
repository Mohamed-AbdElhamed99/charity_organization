<?php

namespace App\Http\Controllers\Site;

use App\Contracts\Services\ContactMessageServiceInterface;
use App\DTOs\CreateContactMessageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Contact\StoreContactMessageRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactMessageServiceInterface $contactMessageService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('site/contact/contact-index');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->contactMessageService->createMessage(new CreateContactMessageDTO(
            fullname: $validated['fullname'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            subject: $validated['subject'],
            message: $validated['message'],
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thank you! We received your message and will reply soon.'),
        ]);

        return back();
    }
}
