<?php

namespace App\Exports;

class BeneficiarySupportReportExportMapper
{
    /**
     * @return list<string>
     */
    public static function headings(): array
    {
        return [
            'Campaign ID',
            'Campaign Title (EN)',
            'Campaign Title (AR)',
            'Support Date',
            'Status',
            'Item',
            'Quantity',
            'Unit Cost (USD)',
            'Line Total (USD)',
            'Campaign Expense ID',
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return list<string|int|float|null>
     */
    public static function mapLine(array $line): array
    {
        return [
            $line['campaign_id'],
            $line['campaign_title_en'],
            $line['campaign_title_ar'],
            $line['supported_at'],
            $line['status'],
            $line['item_name_snapshot'],
            (int) $line['quantity'],
            ((int) $line['unit_cost']) / 100,
            ((int) $line['total_cost']) / 100,
            $line['campaign_expense_id'],
        ];
    }
}
