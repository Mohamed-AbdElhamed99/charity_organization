<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\DTOs\PaymentIntentData;
use Stripe\Event;

class FakePaymentGateway implements PaymentGateway
{
    public int $actualFeeCents = 300;

    /** @var array<int, PaymentIntentData> */
    private array $intents = [];

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
}
