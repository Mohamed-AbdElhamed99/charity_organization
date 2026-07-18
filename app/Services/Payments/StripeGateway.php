<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\DTOs\PaymentIntentData;
use App\DTOs\PaymentMethodData;
use App\DTOs\SetupIntentData;
use App\DTOs\SubscriptionIntentData;
use App\Enums\RecurrenceFrequency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGateway
{
    public function __construct(private StripeClient $client) {}

    public function estimateFee(int $amountCents, string $currency): int
    {
        $percent = (string) config('services.stripe.fee_percent');
        $fixed = (int) config('services.stripe.fee_fixed_cents');

        $percentFee = (int) bcmul(
            bcdiv($percent, '100', 10),
            (string) $amountCents,
            0
        );

        return $percentFee + $fixed;
    }

    public function grossUpForFee(int $intendedCents, string $currency): int
    {
        $percent = (string) config('services.stripe.fee_percent');
        $fixed = (int) config('services.stripe.fee_fixed_cents');
        $buffer = (int) config('services.stripe.fee_buffer_cents', 0);

        $p = bcdiv($percent, '100', 10);
        $denominator = bcsub('1', $p, 10);
        $sum = bcadd((string) $intendedCents, (string) $fixed, 10);
        $quotient = bcdiv($sum, $denominator, 10);
        $chargeCents = (int) ceil((float) $quotient);

        return $chargeCents + $buffer;
    }

    public function createPaymentIntent(int $chargeCents, string $currency, array $metadata): PaymentIntentData
    {
        $intent = $this->client->paymentIntents->create([
            'amount' => $chargeCents,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => $metadata,
        ]);

        return new PaymentIntentData(
            id: $intent->id,
            clientSecret: $intent->client_secret,
            amount: $intent->amount,
            currency: $intent->currency,
        );
    }

    public function actualFeeFor(string $chargeId): int|null
    {
        sleep(3);
        $charge = $this->client->charges->retrieve($chargeId, [
            'expand' => ['balance_transaction'],
        ]);

        $balanceTransaction = $charge->balance_transaction;

        if (is_string($balanceTransaction)) {
            $balanceTransaction = $this->client->balanceTransactions->retrieve($balanceTransaction);
        }

        return (int) $balanceTransaction?->fee ?? null;
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        try {
            return Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::info("error msg " . $e->getMessage());
            Log::info("payload" , [$payload]);
            Log::info("sigHeader",  [$sigHeader]);
            throw new UnexpectedValueException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function findOrCreateCustomer(string $email, array $attributes): string
    {
        $existing = $this->client->customers->search([
            'query' => sprintf('email:"%s"', str_replace('"', '', $email)),
            'limit' => 1,
        ]);

        if (($existing->data[0] ?? null) !== null) {
            return $existing->data[0]->id;
        }

        $customer = $this->client->customers->create([
            'email' => $email,
            'name' => $attributes['name'] ?? null,
            'phone' => $attributes['phone'] ?? null,
        ]);

        return $customer->id;
    }

    public function createSubscription(string $customerId, int $amountCents, string $currency, RecurrenceFrequency $frequency, array $metadata): SubscriptionIntentData
    {
        $subscription = $this->client->subscriptions->create([
            'customer' => $customerId,
            'items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product' => $this->donationProductId(),
                    'unit_amount' => $amountCents,
                    'recurring' => $frequency->toStripeRecurring(),
                ],
            ]],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription',
            ],
            // `confirmation_secret` is itself an expandable field on the
            // invoice, so it must be expanded via the nested path below;
            // expanding only `latest_invoice` leaves it null.
            'expand' => ['latest_invoice.confirmation_secret'],
            'metadata' => $metadata,
        ]);

        // Newer Stripe API versions surface the first invoice's PaymentIntent
        // client secret via `confirmation_secret` rather than a direct
        // `payment_intent` reference. The PaymentIntent ID is recoverable
        // from the client secret itself (format: "pi_xxx_secret_yyy").
        $clientSecret = $subscription->latest_invoice?->confirmation_secret?->client_secret;

        if ($clientSecret === null) {
            throw new RuntimeException("Stripe subscription {$subscription->id} was created without a confirmable client secret on its latest invoice.");
        }

        $paymentIntentId = strstr($clientSecret, '_secret_', true) ?: $clientSecret;

        return new SubscriptionIntentData(
            subscriptionId: $subscription->id,
            clientSecret: $clientSecret,
            paymentIntentId: $paymentIntentId,
            customerId: $customerId,
            amount: $amountCents,
            currency: strtolower($currency),
            billingCycleAnchor: $subscription->billing_cycle_anchor,
        );
    }

    public function createBillingPortalSession(string $customerId, string $returnUrl): string
    {
        $session = $this->client->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);

        return $session->url;
    }

    public function resolveInvoicePaymentDetails(string $invoiceId): array
    {
        $invoice = $this->client->invoices->retrieve($invoiceId, [
            'expand' => ['payments.data.payment.payment_intent.latest_charge'],
        ]);

        $payment = $invoice->payments?->data[0]->payment ?? null;
        $paymentIntent = $payment?->payment_intent;

        if ($paymentIntent === null) {
            return ['paymentIntentId' => null, 'chargeId' => null];
        }

        $chargeId = $paymentIntent->latest_charge !== null
            ? (is_string($paymentIntent->latest_charge) ? $paymentIntent->latest_charge : $paymentIntent->latest_charge->id)
            : null;

        return [
            'paymentIntentId' => $paymentIntent->id,
            'chargeId' => $chargeId,
        ];
    }

    public function createSetupIntent(string $customerId): SetupIntentData
    {
        $setupIntent = $this->client->setupIntents->create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return new SetupIntentData(
            setupIntentId: $setupIntent->id,
            clientSecret: $setupIntent->client_secret,
            customerId: $customerId,
        );
    }

    public function retrievePaymentMethod(string $paymentMethodId): PaymentMethodData
    {
        $paymentMethod = $this->client->paymentMethods->retrieve($paymentMethodId);
        $card = $paymentMethod->card;

        return new PaymentMethodData(
            id: $paymentMethod->id,
            brand: (string) $card?->brand,
            last4: (string) $card?->last4,
            expMonth: (int) $card?->exp_month,
            expYear: (int) $card?->exp_year,
        );
    }

    public function detachPaymentMethod(string $paymentMethodId): void
    {
        $this->client->paymentMethods->detach($paymentMethodId);
    }

    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        $this->client->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        $this->client->subscriptions->cancel($subscriptionId);
    }

    /**
     * The single Stripe Product recurring donation Subscriptions are billed
     * against. Uses the configured ID if set, otherwise creates one lazily
     * and caches it so it is only created once per environment.
     */
    private function donationProductId(): string
    {
        $configured = config('services.stripe.donation_product_id');

        if ($configured) {
            return $configured;
        }

        return Cache::rememberForever('stripe_donation_product_id', function () {
            $product = $this->client->products->create([
                'name' => 'Recurring Donation',
                'metadata' => ['app' => config('app.name')],
            ]);

            return $product->id;
        });
    }
}