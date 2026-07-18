<?php

namespace App\DTOs;

readonly class SubscriptionIntentData
{
    public function __construct(
        public string $subscriptionId,
        public string $clientSecret,
        public string $paymentIntentId,
        public string $customerId,
        public int $amount,
        public string $currency,
        public ?int $billingCycleAnchor = null,
    ) {}
}
