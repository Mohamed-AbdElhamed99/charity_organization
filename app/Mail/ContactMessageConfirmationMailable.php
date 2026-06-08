<?php

namespace App\Mail;

use App\Models\ContactUs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageConfirmationMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactUs $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your message'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact.confirmation',
            with: [
                'contactMessage' => $this->contactMessage,
            ],
        );
    }
}
