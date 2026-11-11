<?php

namespace App\Enums;

enum TurkeyRequestStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
