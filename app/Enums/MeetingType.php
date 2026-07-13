<?php

namespace App\Enums;

enum MeetingType: string
{
    case Board = 'board';
    case Committee = 'committee';
    case GeneralAssembly = 'general_assembly';
    case Field = 'field';
    case Emergency = 'emergency';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Board => 'Board',
            self::Committee => 'Committee',
            self::GeneralAssembly => 'General Assembly',
            self::Field => 'Field',
            self::Emergency => 'Emergency',
            self::Other => 'Other',
        };
    }
}
