<?php

namespace App\Http\Controllers\Site\Account;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\DonationSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountDonationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $donations = Donation::query()
            ->where('donor_id', $user->id)
            ->with('campaign')
            ->latest()
            ->paginate(10, ['*'], 'donations_page');

        $subscriptions = DonationSubscription::query()
            ->where('donor_id', $user->id)
            ->with('allocations.campaign')
            ->latest()
            ->get();

        return Inertia::render('site/account/donations', [
            'donations' => $donations,
            'subscriptions' => $subscriptions,
        ]);
    }
}
