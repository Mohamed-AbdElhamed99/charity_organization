<?php

namespace App\Enums;

enum StripeStatus: string
{
    case Pending   = 'pending';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
    case Refunded  = 'refunded';

    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }
}
