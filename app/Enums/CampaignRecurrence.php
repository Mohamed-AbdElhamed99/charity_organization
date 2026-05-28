<?php

namespace App\Enums;

enum CampaignRecurrence: string
{
    case Never   = 'never';
    case Daily   = 'daily';
    case Weekly  = 'weekly';
    case Monthly = 'monthly';

    public function isRecurring(): bool
    {
        return $this !== self::Never;
    }
}
