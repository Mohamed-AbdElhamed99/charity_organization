<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Active    => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isPublishable(): bool
    {
        return match($this) {
            self::Active, self::Completed => true,
            default                       => false,
        };
    }
}
