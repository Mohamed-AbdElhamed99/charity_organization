<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    /**
     * Maps this frequency to the Stripe `price_data.recurring` shape.
     *
     * @return array{interval: string, interval_count: int}
     */
    public function toStripeRecurring(): array
    {
        return match ($this) {
            self::Weekly => ['interval' => 'week', 'interval_count' => 1],
            self::Monthly => ['interval' => 'month', 'interval_count' => 1],
            self::Quarterly => ['interval' => 'month', 'interval_count' => 3],
            self::Yearly => ['interval' => 'year', 'interval_count' => 1],
        };
    }
}
