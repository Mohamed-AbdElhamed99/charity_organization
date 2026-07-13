<?php

namespace App\Enums;

enum MinutesLanguage: string
{
    case Ar = 'ar';
    case En = 'en';
    case Bilingual = 'bilingual';

    public function label(): string
    {
        return match ($this) {
            self::Ar => 'Arabic',
            self::En => 'English',
            self::Bilingual => 'Bilingual',
        };
    }
}
