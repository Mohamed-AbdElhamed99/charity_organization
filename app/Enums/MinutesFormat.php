<?php

namespace App\Enums;

enum MinutesFormat: string
{
    case Standard = 'standard';
    case Formal = 'formal';
    case Simplified = 'simplified';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Formal => 'Formal',
            self::Simplified => 'Simplified',
        };
    }
}
