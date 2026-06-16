<?php

namespace App\Services;

use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BeneficiarySupportReportService
{
    /**
     * Shared query used by page and export.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function query(Beneficiary $beneficiary, array $filters): Collection
    {
        $query = DB::table('beneficiary_support_items as bsi')
            ->join('beneficiary_supports as bs', 'bs.id', '=', 'bsi.beneficiary_support_id')
            ->join('campaigns as c', 'c.id', '=', 'bs.campaign_id')
            ->leftJoin('aid_items as ai', 'ai.id', '=', 'bsi.aid_item_id')
            ->where('bs.beneficiary_id', $beneficiary->id)
            ->select([
                'bs.id as support_id',
                'bs.supported_at',
                'bs.status',
                'c.id as campaign_id',
                'c.title_ar as campaign_title_ar',
                'c.title_en as campaign_title_en',
                'bsi.id as line_id',
                'bsi.item_name_snapshot',
                'bsi.quantity',
                'bsi.unit_cost',
                'bsi.total_cost',
                'bsi.campaign_expense_id',
                'ai.id as aid_item_id',
            ]);

        $query
            ->when(
                $filters['from'] ?? null,
                fn ($q, $from) => $q->whereDate('bs.supported_at', '>=', $from)
            )
            ->when(
                $filters['to'] ?? null,
                fn ($q, $to) => $q->whereDate('bs.supported_at', '<=', $to)
            )
            ->when(
                $filters['campaign_id'] ?? null,
                fn ($q, $campaignId) => $q->where('bs.campaign_id', (int) $campaignId)
            )
            ->when(
                $filters['aid_item_id'] ?? null,
                fn ($q, $aidItemId) => $q->where('bsi.aid_item_id', (int) $aidItemId)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($q, $status) => $q->where('bs.status', $status)
            );

        return $query
            ->orderBy('bs.supported_at')
            ->orderBy('c.id')
            ->orderBy('bsi.id')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function groupByCampaign(Collection $rows): array
    {
        return $rows
            ->groupBy('campaign_id')
            ->map(function (Collection $campaignRows): array {
                $first = $campaignRows->first();
                $campaignTotal = (int) $campaignRows->sum('total_cost');
                $itemsCount = (int) $campaignRows->sum('quantity');
                $firstDate = $campaignRows->min('supported_at');
                $lastDate = $campaignRows->max('supported_at');

                return [
                    'campaign_id' => (int) $first->campaign_id,
                    'campaign_title_ar' => $first->campaign_title_ar,
                    'campaign_title_en' => $first->campaign_title_en,
                    'date_from' => $firstDate,
                    'date_to' => $lastDate,
                    'items_count' => $itemsCount,
                    'campaign_total_cost' => $campaignTotal,
                    'lines' => $campaignRows->map(fn ($row) => [
                        'line_id' => (int) $row->line_id,
                        'support_id' => (int) $row->support_id,
                        'supported_at' => $row->supported_at,
                        'status' => $row->status,
                        'item_name_snapshot' => $row->item_name_snapshot,
                        'quantity' => (int) $row->quantity,
                        'unit_cost' => (int) $row->unit_cost,
                        'total_cost' => (int) $row->total_cost,
                        'aid_item_id' => $row->aid_item_id !== null ? (int) $row->aid_item_id : null,
                        'campaign_expense_id' => $row->campaign_expense_id !== null ? (int) $row->campaign_expense_id : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, int>
     */
    public function totals(Collection $rows): array
    {
        return [
            'grand_total_cost' => (int) $rows->sum('total_cost'),
            'total_items' => (int) $rows->sum('quantity'),
            'campaigns_count' => (int) $rows->pluck('campaign_id')->unique()->count(),
        ];
    }
}
