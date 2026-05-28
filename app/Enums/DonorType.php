<?php

namespace App\Enums;

enum DonorType: string
{
    case Individual   = 'individual';
    case Organization = 'organization';

    public function label(): string
    {
        return match($this) {
            self::Individual   => 'Individual',
            self::Organization => 'Organization',
        };
    }
}
