<?php

namespace App\Support;

/**
 * Integer-cent money helpers. Ledger DB columns remain decimal; convert at boundaries only.
 */
final class Money
{
    public static function centsToDecimal(int $cents): string
    {
        return bcdiv((string) $cents, '100', 2);
    }

    public static function decimalToCents(string|float|int $decimal): int
    {
        return (int) bcmul((string) $decimal, '100', 0);
    }

    public static function formatUsd(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
