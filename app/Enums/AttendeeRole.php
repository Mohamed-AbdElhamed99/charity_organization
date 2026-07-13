<?php

namespace App\Enums;

enum AttendeeRole: string
{
    case Chair = 'chair';
    case Secretary = 'secretary';
    case Member = 'member';
    case Observer = 'observer';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Chair => 'Chair',
            self::Secretary => 'Secretary',
            self::Member => 'Member',
            self::Observer => 'Observer',
            self::Guest => 'Guest',
        };
    }
}
