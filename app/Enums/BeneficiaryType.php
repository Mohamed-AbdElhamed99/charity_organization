<?php

namespace App\Enums;

enum BeneficiaryType: string
{
    case Individual   = 'individual';
    case Family       = 'family';
    case Organization = 'organization';

    public function label(): string
    {
        return match($this) {
            self::Individual   => 'Individual',
            self::Family       => 'Family / Household',
            self::Organization => 'Organization',
        };
    }

    /** Returns the profile relationship method name on the Beneficiary model */
    public function profileRelation(): string
    {
        return match($this) {
            self::Individual   => 'individual',
            self::Family       => 'family',
            self::Organization => 'organization',
        };
    }
}
