<?php

namespace App\Enums;

enum AccountType: string
{
    case Bank    = 'bank';
    case Cash    = 'cash';
    case Digital = 'digital';

    public function label(): string
    {
        return match($this) {
            self::Bank    => 'Bank Account',
            self::Cash    => 'Cash / Petty Cash',
            self::Digital => 'Digital Wallet',
        };
    }
}
