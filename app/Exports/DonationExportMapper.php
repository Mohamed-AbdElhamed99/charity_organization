<?php

namespace App\Exports;

use App\Models\Donation;

class DonationExportMapper
{
    /**
     * @return list<string>
     */
    public static function headings(): array
    {
        return [
            'Date',
            'Donor Name',
            'Email',
            'Campaign',
            'Gift (USD)',
            'Fee (USD)',
            'Gross Charged (USD)',
            'Net (USD)',
            'Currency',
            'Status',
            'Fee Covered',
            'Payment Intent ID',
            'Anonymous Flag',
        ];
    }

    /**
     * @return list<string|int|float|null>
     */
    public static function mapRow(Donation $donation): array
    {
        $donation->loadMissing(['donor', 'campaign', 'transaction.currency']);

        return [
            $donation->created_at?->format('Y-m-d H:i'),
            $donation->donor_admin_name,
            $donation->donor?->email,
            $donation->is_general ? 'General' : ($donation->campaign?->title ?? ''),
            $donation->amount / 100,
            $donation->transaction ? (float) $donation->transaction->fee_amount : null,
            $donation->transaction ? (float) $donation->transaction->gross_amount : null,
            $donation->transaction ? (float) $donation->transaction->net_amount : null,
            $donation->transaction?->currency?->code,
            $donation->status?->value,
            $donation->donor_covers_fee ? 'Yes' : 'No',
            $donation->stripe_payment_intent_id,
            $donation->is_anonymous ? 'Yes' : 'No',
        ];
    }
}
