<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    case PendingAssessment = 'pending_assessment';
    case Active            = 'active';
    case Inactive          = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::PendingAssessment => 'Pending Assessment',
            self::Active            => 'Active',
            self::Inactive          => 'Inactive',
        };
    }

    public function isApproved(): bool
    {
        return $this === self::Active;
    }
}
