<?php

namespace App\Services;

use App\Mail\DonationSubmittedMailable;
use App\Models\Donation;
use Illuminate\Support\Facades\Mail;

class DonationSubmittedNotifier
{
    public function notify(Donation $donation): void
    {
        $donation->loadMissing(['donor', 'campaign', 'appeal', 'paymentMethod']);

        try {
            Mail::to(config('site.admin_notification_email'))
                ->queue(new DonationSubmittedMailable($donation));
        } catch (\Throwable) {
            // The donation is already stored. A mail failure must not undo it.
        }
    }
}
