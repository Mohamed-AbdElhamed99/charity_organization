<?php

namespace App\Enums;

enum DecisionType: string
{
    case Resolution = 'resolution';
    case ActionItem = 'action_item';
    case Recommendation = 'recommendation';
    case Policy = 'policy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Resolution => 'Resolution',
            self::ActionItem => 'Action Item',
            self::Recommendation => 'Recommendation',
            self::Policy => 'Policy',
            self::Other => 'Other',
        };
    }
}
