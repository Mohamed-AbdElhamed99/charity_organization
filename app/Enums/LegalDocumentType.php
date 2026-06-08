<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';

    public function label(): string
    {
        return match ($this) {
            self::Terms => __('Terms & Conditions'),
            self::Privacy => __('Privacy Policy'),
        };
    }

    public function routeSlug(): string
    {
        return $this->value;
    }
}
