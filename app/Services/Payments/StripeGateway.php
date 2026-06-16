<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\DTOs\PaymentIntentData;
use Illuminate\Support\Facades\Log;
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

    public function actualFeeFor(string $chargeId): int
    {
        $charge = $this->client->charges->retrieve($chargeId, [
            'expand' => ['balance_transaction'],
        ]);

        $balanceTransaction = $charge->balance_transaction;

        if (is_string($balanceTransaction)) {
            $balanceTransaction = $this->client->balanceTransactions->retrieve($balanceTransaction);
        }

        return (int) $balanceTransaction->fee;
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
}