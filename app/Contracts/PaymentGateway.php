<?php

namespace App\Contracts;

use App\DTOs\PaymentIntentData;
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
}