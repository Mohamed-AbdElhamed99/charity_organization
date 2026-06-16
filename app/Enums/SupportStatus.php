<?php

namespace App\Enums;

enum SupportStatus: string
{
    case Planned = 'planned';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
