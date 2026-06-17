<?php

namespace App\Contracts;

use App\DTOs\PaymentIntentData;
use Stripe\Event;

interface PaymentGateway
{
    public function estimateFee(int $amountCents, string $currency): int;

    public function grossUpForFee(int $intendedCents, string $currency): int;

    public function createPaymentIntent(int $chargeCents, string $currency, array $metadata): PaymentIntentData;

    public function actualFeeFor(string $chargeId):  int|null;

    public function constructWebhookEvent(string $payload, string $sigHeader): Event;
}