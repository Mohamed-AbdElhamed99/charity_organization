<?php

namespace App\Contracts;

use App\DTOs\PaymentIntentData;
use App\DTOs\PaymentMethodData;
use App\DTOs\SetupIntentData;
use App\DTOs\SubscriptionIntentData;
use App\Enums\RecurrenceFrequency;
use Stripe\Event;

interface PaymentGateway
{
    public function estimateFee(int $amountCents, string $currency): int;

    public function grossUpForFee(int $intendedCents, string $currency): int;

    public function createPaymentIntent(int $chargeCents, string $currency, array $metadata): PaymentIntentData;

    public function actualFeeFor(string $chargeId):  int|null;

    public function constructWebhookEvent(string $payload, string $sigHeader): Event;

    /**
     * Find or create a Stripe Customer for the given donor, keyed by email.
     */
    public function findOrCreateCustomer(string $email, array $attributes): string;

    /**
     * Create a recurring Subscription for the given amount and frequency,
     * returning the client secret needed to confirm the first invoice's
     * PaymentIntent.
     */
    public function createSubscription(string $customerId, int $amountCents, string $currency, RecurrenceFrequency $frequency, array $metadata): SubscriptionIntentData;

    /**
     * Create a Stripe-hosted Customer Portal session so a donor can manage
     * or cancel their recurring donation without a login on this site.
     */
    public function createBillingPortalSession(string $customerId, string $returnUrl): string;

    /**
     * Resolve the PaymentIntent and Charge IDs for a paid invoice, used to
     * reconcile a subscription's recurring donation into the ledger.
     *
     * @return array{paymentIntentId: string|null, chargeId: string|null}
     */
    public function resolveInvoicePaymentDetails(string $invoiceId): array;

    /**
     * Create a SetupIntent so a card can be validated and attached to the
     * Stripe Customer for future use, without charging anything.
     */
    public function createSetupIntent(string $customerId): SetupIntentData;

    /**
     * Fetch display details (brand/last4/expiry) for a payment method,
     * confirming it belongs to the given customer.
     */
    public function retrievePaymentMethod(string $paymentMethodId): PaymentMethodData;

    public function detachPaymentMethod(string $paymentMethodId): void;

    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): void;

    public function cancelSubscription(string $subscriptionId): void;
}