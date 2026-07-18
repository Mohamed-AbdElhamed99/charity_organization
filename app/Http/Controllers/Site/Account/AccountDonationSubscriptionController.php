<?php

namespace App\Http\Controllers\Site\Account;

use App\Contracts\PaymentGateway;
use App\Enums\DonationSubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\DonationSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountDonationSubscriptionController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function cancel(Request $request, DonationSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->donor_id === $request->user()->id, 403);

        $this->gateway->cancelSubscription($subscription->stripe_subscription_id);

        $subscription->update(['status' => DonationSubscriptionStatus::Canceled]);

        return back()->with('status', __('Your recurring donation was canceled.'));
    }
}
