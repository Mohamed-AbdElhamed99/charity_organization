<?php

namespace App\Enums;

enum MeetingLocationType: string
{
    case Physical = 'physical';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Online => 'Online',
            self::Hybrid => 'Hybrid',
        };
    }
}
