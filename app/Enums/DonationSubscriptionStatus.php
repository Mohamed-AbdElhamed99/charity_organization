<?php

namespace App\Enums;

enum DonationSubscriptionStatus: string
{
    case Active   = 'active';
    case PastDue  = 'past_due';
    case Canceled = 'canceled';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
