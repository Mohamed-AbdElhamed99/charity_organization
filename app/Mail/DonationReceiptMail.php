<?php

namespace App\Mail;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your donation receipt'),
        );
    }

    public function content(): Content
    {
        $this->donation->loadMissing(['campaign', 'donor', 'transaction']);

        return new Content(
            markdown: 'mail.donation-receipt',
            with: [
                'giftAmount' => Money::formatUsd($this->donation->amount),
                'campaignLabel' => $this->donation->is_general
                    ? __('General Fund')
                    : ($this->donation->campaign?->title ?? __('Campaign')),
                'feeCovered' => $this->donation->donor_covers_fee,
                'date' => $this->donation->created_at?->format('F j, Y'),
                'reference' => $this->donation->stripe_payment_intent_id ?? (string) $this->donation->id,
                'donorName' => $this->donation->donor?->name,
            ],
        );
    }
}
