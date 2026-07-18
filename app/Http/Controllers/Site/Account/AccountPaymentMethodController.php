<?php

namespace App\Http\Controllers\Site\Account;

use App\Contracts\PaymentGateway;
use App\Enums\DonorType;
use App\Http\Controllers\Controller;
use App\Models\DonorPaymentMethod;
use App\Models\DonorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountPaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function index(Request $request): Response
    {
        $methods = $request->user()->donorPaymentMethods()->orderByDesc('is_default')->orderByDesc('id')->get();

        return Inertia::render('site/account/payment-methods', [
            'paymentMethods' => $methods,
            'stripePublishableKey' => config('services.stripe.key'),
        ]);
    }

    public function createSetupIntent(Request $request): JsonResponse
    {
        $customerId = $this->resolveStripeCustomerId($request);

        $setupIntent = $this->gateway->createSetupIntent($customerId);

        return response()->json([
            'client_secret' => $setupIntent->clientSecret,
        ]);
    }

    /**
     * Persist a saved card only after the client has confirmed its
     * SetupIntent, so an unconfirmed/unattached card is never stored.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method_id' => ['required', 'string'],
        ]);

        $user = $request->user();
        $customerId = $this->resolveStripeCustomerId($request);
        $details = $this->gateway->retrievePaymentMethod($validated['payment_method_id']);

        $isFirst = $user->donorPaymentMethods()->count() === 0;

        $method = DonorPaymentMethod::query()->firstOrCreate(
            ['stripe_payment_method_id' => $details->id],
            [
                'user_id' => $user->id,
                'brand' => $details->brand,
                'last4' => $details->last4,
                'exp_month' => $details->expMonth,
                'exp_year' => $details->expYear,
                'is_default' => $isFirst,
            ],
        );

        if ($isFirst) {
            $this->gateway->setDefaultPaymentMethod($customerId, $method->stripe_payment_method_id);
        }

        return back()->with('status', __('Payment method saved.'));
    }

    public function destroy(Request $request, DonorPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->user_id === $request->user()->id, 403);

        $this->gateway->detachPaymentMethod($paymentMethod->stripe_payment_method_id);
        $paymentMethod->delete();

        return back()->with('status', __('Payment method removed.'));
    }

    public function setDefault(Request $request, DonorPaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless($paymentMethod->user_id === $request->user()->id, 403);

        $customerId = $this->resolveStripeCustomerId($request);
        $this->gateway->setDefaultPaymentMethod($customerId, $paymentMethod->stripe_payment_method_id);

        DB::transaction(function () use ($request, $paymentMethod) {
            $request->user()->donorPaymentMethods()->update(['is_default' => false]);
            $paymentMethod->update(['is_default' => true]);
        });

        return back()->with('status', __('Default payment method updated.'));
    }

    private function resolveStripeCustomerId(Request $request): string
    {
        $user = $request->user();
        $profile = DonorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['type' => DonorType::Individual],
        );

        if ($profile->stripe_customer_id) {
            return $profile->stripe_customer_id;
        }

        $customerId = $this->gateway->findOrCreateCustomer($user->email, [
            'name' => $user->name,
            'phone' => $user->phone,
        ]);

        $profile->update(['stripe_customer_id' => $customerId]);

        return $customerId;
    }
}
