<?php

namespace App\Jobs;

use App\Mail\DonationReceiptMail;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDonationReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $donation = $this->donation->fresh(['donor', 'campaign', 'transaction']);

        if ($donation === null || $donation->receipt_sent_at !== null) {
            return;
        }

        $email = $donation->donor?->email;

        if ($email === null) {
            return;
        }

        Mail::to($email)->send(new DonationReceiptMail($donation));

        $donation->update(['receipt_sent_at' => now()]);
    }
}
