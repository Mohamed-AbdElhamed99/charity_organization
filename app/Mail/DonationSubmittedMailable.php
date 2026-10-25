<?php

namespace App\Mail;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationSubmittedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Donation $donation) {}

    public function envelope(): Envelope
    {
        $method = $this->donation->paymentMethod?->name ?? __('Donation');

        return new Envelope(
            subject: __('New :method donation submitted', ['method' => $method]),
        );
    }

    public function content(): Content
    {
        $this->donation->loadMissing(['donor', 'campaign', 'appeal', 'paymentMethod']);

        return new Content(
            markdown: 'mail.donation-submitted',
            with: [
                'donorName' => $this->donation->donor_admin_name,
                'donorEmail' => $this->donation->donor?->email,
                'donorPhone' => $this->donation->donor?->phone,
                'amount' => Money::formatUsd((int) $this->donation->amount),
                'purpose' => $this->donation->purpose_label,
                'appeal' => $this->donation->unit_label,
                'method' => $this->donation->paymentMethod?->name ?? __('Card'),
                'status' => $this->donation->status?->value,
                'adminUrl' => route('admin.donations.index'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $media = $this->donation->getFirstMedia('zelle_receipt');

        if ($media === null) {
            return [];
        }

        return [
            Attachment::fromPath($media->getPath())
                ->as($media->file_name)
                ->withMime($media->mime_type),
        ];
    }
}
