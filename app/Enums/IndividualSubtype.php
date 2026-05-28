<?php

namespace App\Enums;

enum IndividualSubtype: string
{
    case Adult = 'adult';
    case Child = 'child';

    public function isChild(): bool
    {
        return $this === self::Child;
    }
}
