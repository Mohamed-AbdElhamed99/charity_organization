<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\DTOs\PaymentIntentData;
use App\DTOs\PaymentMethodData;
use App\DTOs\SetupIntentData;
use App\DTOs\SubscriptionIntentData;
use App\Enums\RecurrenceFrequency;
use Stripe\Event;

class FakePaymentGateway implements PaymentGateway
{
    public int $actualFeeCents = 300;

    /** @var array<int, PaymentIntentData> */
    private array $intents = [];

    /** @var array<string, string> */
    private array $customersByEmail = [];

    /** @var array<int, SubscriptionIntentData> */
    private array $subscriptions = [];

    /** @var array<int, SetupIntentData> */
    private array $setupIntents = [];

    /** @var array<string, PaymentMethodData> */
    public array $paymentMethodsById = [];

    /** @var array<int, string> */
    public array $detachedPaymentMethods = [];

    /** @var array<int, array{customerId: string, paymentMethodId: string}> */
    public array $defaultPaymentMethodCalls = [];

    /** @var array<int, string> */
    public array $canceledSubscriptions = [];

    /**
     * Captured arguments of each createSubscription() call, for tests to
     * assert the correct frequency/interval was requested.
     *
     * @var array<int, array{customerId: string, amountCents: int, currency: string, frequency: RecurrenceFrequency, metadata: array}>
     */
    public array $subscriptionCalls = [];

    public function estimateFee(int $amountCents, string $currency): int
    {
        $percentFee = (int) bcmul(bcdiv((string) config('services.stripe.fee_percent'), '100', 10), (string) $amountCents, 0);

        return $percentFee + (int) config('services.stripe.fee_fixed_cents');
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

        return (int) ceil((float) $quotient) + $buffer;
    }

    public function createPaymentIntent(int $chargeCents, string $currency, array $metadata): PaymentIntentData
    {
        $id = 'pi_test_'.count($this->intents);
        $intent = new PaymentIntentData(
            id: $id,
            clientSecret: 'cs_test_'.$id,
            amount: $chargeCents,
            currency: strtolower($currency),
        );
        $this->intents[] = $intent;

        return $intent;
    }

    public function actualFeeFor(string $chargeId): int
    {
        return $this->actualFeeCents;
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        return Event::constructFrom(json_decode($payload, true) ?? []);
    }

    public function findOrCreateCustomer(string $email, array $attributes): string
    {
        return $this->customersByEmail[$email] ??= 'cus_test_'.count($this->customersByEmail);
    }

    public function createSubscription(string $customerId, int $amountCents, string $currency, RecurrenceFrequency $frequency, array $metadata): SubscriptionIntentData
    {
        $id = 'sub_test_'.count($this->subscriptions);
        $paymentIntentId = 'pi_test_sub_'.count($this->subscriptions);

        $this->subscriptionCalls[] = [
            'customerId' => $customerId,
            'amountCents' => $amountCents,
            'currency' => $currency,
            'frequency' => $frequency,
            'metadata' => $metadata,
        ];

        $subscription = new SubscriptionIntentData(
            subscriptionId: $id,
            clientSecret: 'cs_test_'.$paymentIntentId,
            paymentIntentId: $paymentIntentId,
            customerId: $customerId,
            amount: $amountCents,
            currency: strtolower($currency),
            billingCycleAnchor: now()->getTimestamp(),
        );
        $this->subscriptions[] = $subscription;

        return $subscription;
    }

    public function createBillingPortalSession(string $customerId, string $returnUrl): string
    {
        return 'https://billing.stripe.test/session/'.$customerId;
    }

    public function resolveInvoicePaymentDetails(string $invoiceId): array
    {
        return [
            'paymentIntentId' => 'pi_test_invoice_'.$invoiceId,
            'chargeId' => 'ch_test_invoice_'.$invoiceId,
        ];
    }

    public function createSetupIntent(string $customerId): SetupIntentData
    {
        $id = 'seti_test_'.count($this->setupIntents);
        $setupIntent = new SetupIntentData(
            setupIntentId: $id,
            clientSecret: 'cs_test_'.$id,
            customerId: $customerId,
        );
        $this->setupIntents[] = $setupIntent;

        return $setupIntent;
    }

    public function retrievePaymentMethod(string $paymentMethodId): PaymentMethodData
    {
        return $this->paymentMethodsById[$paymentMethodId] ?? new PaymentMethodData(
            id: $paymentMethodId,
            brand: 'visa',
            last4: '4242',
            expMonth: 12,
            expYear: (int) now()->addYear()->format('Y'),
        );
    }

    public function detachPaymentMethod(string $paymentMethodId): void
    {
        $this->detachedPaymentMethods[] = $paymentMethodId;
    }

    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        $this->defaultPaymentMethodCalls[] = ['customerId' => $customerId, 'paymentMethodId' => $paymentMethodId];
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        $this->canceledSubscriptions[] = $subscriptionId;
    }
}
