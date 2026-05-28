<?php

namespace App\Enums;

enum TransferRecipientType: string
{
    case Vendor      = 'vendor';
    case Beneficiary = 'beneficiary';
    case User        = 'user';
    case Other       = 'other';

    public function label(): string
    {
        return match($this) {
            self::Vendor      => 'Vendor / Supplier',
            self::Beneficiary => 'Beneficiary',
            self::User        => 'Staff Reimbursement',
            self::Other       => 'Other',
        };
    }
}
