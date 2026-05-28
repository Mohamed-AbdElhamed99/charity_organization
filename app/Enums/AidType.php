<?php

namespace App\Enums;

enum AidType: string
{
    case Financial = 'financial';
    case InKind    = 'in_kind';
    case Both      = 'both';

    public function label(): string
    {
        return match($this) {
            self::Financial => 'Financial',
            self::InKind    => 'In-Kind',
            self::Both      => 'Financial & In-Kind',
        };
    }
}
