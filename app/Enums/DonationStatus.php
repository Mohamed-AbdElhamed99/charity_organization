<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case RequiresAction = 'requires_action';

    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed, self::Refunded], true);
    }
}
