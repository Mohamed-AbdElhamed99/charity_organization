<?php

namespace App\Mail;

use App\Models\ContactUs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactUs $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('New contact form submission: :subject', [
                'subject' => $this->contactMessage->subject,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact.received',
            with: [
                'contactMessage' => $this->contactMessage,
            ],
        );
    }
}
