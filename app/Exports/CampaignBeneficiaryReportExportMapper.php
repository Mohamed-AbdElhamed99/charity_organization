<?php

namespace App\Exports;

class CampaignBeneficiaryReportExportMapper
{
    /**
     * @return list<string>
     */
    public static function headings(): array
    {
        return [
            'Beneficiary Code',
            'Beneficiary Name',
            'Beneficiary Type',
            'Support Events',
            'Items',
            'Total Cost (USD)',
            'Last Supported At',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string|int|float|null>
     */
    public static function mapRow(array $row): array
    {
        return [
            $row['beneficiary_code'],
            $row['beneficiary_name'],
            $row['beneficiary_type'],
            (int) $row['support_events_count'],
            (int) $row['items_count'],
            ((int) $row['total_cost']) / 100,
            $row['last_supported_at'],
        ];
    }
}
