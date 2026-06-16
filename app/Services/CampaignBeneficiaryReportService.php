<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignBeneficiaryReportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Campaign $campaign, array $filters): LengthAwarePaginator
    {
        $query = $this->query($campaign, $filters)
            ->orderByDesc('last_supported_at');

        return DB::query()
            ->fromSub($query, 'campaign_beneficiary_rows')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    /**
     * Shared grouped query for table and exports.
     *
     * @param  array<string, mixed>  $filters
     */
    public function query(Campaign $campaign, array $filters): Builder
    {
        $query = DB::table('beneficiary_supports as bs')
            ->join('beneficiaries as b', 'b.id', '=', 'bs.beneficiary_id')
            ->leftJoin('beneficiary_support_items as bsi', 'bsi.beneficiary_support_id', '=', 'bs.id')
            ->leftJoin('beneficiary_individuals as bi', 'bi.beneficiary_id', '=', 'b.id')
            ->leftJoin('beneficiary_families as bf', 'bf.beneficiary_id', '=', 'b.id')
            ->leftJoin('beneficiary_organizations as bo', 'bo.beneficiary_id', '=', 'b.id')
            ->where('bs.campaign_id', $campaign->id)
            ->select([
                'b.id as beneficiary_id',
                'b.code as beneficiary_code',
                'b.type as beneficiary_type',
                DB::raw('MAX(COALESCE(CONCAT_WS(" ", bi.first_name, bi.last_name), bf.household_name, bo.name, b.code)) as beneficiary_name'),
                DB::raw('COUNT(DISTINCT bs.id) as support_events_count'),
                DB::raw('COALESCE(SUM(bsi.quantity), 0) as items_count'),
                DB::raw('COALESCE(SUM(bsi.total_cost), 0) as total_cost'),
                DB::raw('MAX(bs.supported_at) as last_supported_at'),
            ])
            ->groupBy('b.id', 'b.code', 'b.type');

        $this->applyRowFilters($query, $filters);

        if (isset($filters['cost_min'])) {
            $query->havingRaw('COALESCE(SUM(bsi.total_cost), 0) >= ?', [(int) $filters['cost_min']]);
        }

        if (isset($filters['cost_max'])) {
            $query->havingRaw('COALESCE(SUM(bsi.total_cost), 0) <= ?', [(int) $filters['cost_max']]);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function exportRows(Campaign $campaign, array $filters): Collection
    {
        return $this->query($campaign, $filters)
            ->orderByDesc('last_supported_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summarize(Campaign $campaign, array $filters): array
    {
        $supportBase = $this->buildSupportBaseQuery($campaign, $filters);
        $lineBase = $this->buildLineBaseQuery($campaign, $filters);

        $distinctBeneficiaries = (clone $supportBase)
            ->distinct('bs.beneficiary_id')
            ->count('bs.beneficiary_id');

        $supportEvents = (clone $supportBase)
            ->distinct('bs.id')
            ->count('bs.id');

        $totalItems = (clone $lineBase)
            ->sum('bsi.quantity');

        $totalCost = (clone $lineBase)
            ->sum('bsi.total_cost');

        $byAidItem = (clone $lineBase)
            ->selectRaw('COALESCE(a.name, JSON_OBJECT("en", bsi.item_name_snapshot, "ar", bsi.item_name_snapshot)) as item_name')
            ->selectRaw('bsi.aid_item_id')
            ->selectRaw('SUM(bsi.quantity) as total_quantity')
            ->selectRaw('SUM(bsi.total_cost) as total_cost')
            ->leftJoin('aid_items as a', 'a.id', '=', 'bsi.aid_item_id')
            ->groupBy('bsi.aid_item_id', 'item_name')
            ->orderByDesc('total_cost')
            ->get();

        $byBeneficiaryType = (clone $lineBase)
            ->join('beneficiaries as b', 'b.id', '=', 'bs.beneficiary_id')
            ->selectRaw('b.type as beneficiary_type')
            ->selectRaw('COUNT(DISTINCT b.id) as beneficiaries_count')
            ->selectRaw('SUM(bsi.total_cost) as total_cost')
            ->groupBy('b.type')
            ->get();

        $campaignExpensesTotalCents = $campaign->expenses()
            ->get()
            ->sum(fn ($expense) => (int) round((float) $expense->amount * 100));

        $allocatedCents = (clone $lineBase)
            ->whereNotNull('bsi.campaign_expense_id')
            ->sum('bsi.total_cost');

        $unallocatedCents = max(0, $campaignExpensesTotalCents - $allocatedCents);

        return [
            'distinct_beneficiaries' => (int) $distinctBeneficiaries,
            'support_events' => (int) $supportEvents,
            'total_items' => (int) $totalItems,
            'total_cost' => (int) $totalCost,
            'by_aid_item' => $byAidItem,
            'by_beneficiary_type' => $byBeneficiaryType,
            'campaign_expenses_total' => (int) $campaignExpensesTotalCents,
            'allocated_against_expenses' => (int) $allocatedCents,
            'unallocated_against_expenses' => (int) $unallocatedCents,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyRowFilters(Builder $query, array $filters): void
    {
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
                $filters['status'] ?? null,
                fn ($q, $status) => $q->where('bs.status', $status)
            )
            ->when(
                $filters['beneficiary_type'] ?? null,
                fn ($q, $type) => $q->where('b.type', $type)
            )
            ->when(
                $filters['aid_item_id'] ?? null,
                fn ($q, $aidItemId) => $q->where('bsi.aid_item_id', (int) $aidItemId)
            )
            ->when(
                $filters['query'] ?? null,
                function ($q, $search) {
                    $q->where(function ($searchQuery) use ($search) {
                        $searchQuery
                            ->where('b.code', 'like', "%{$search}%")
                            ->orWhereRaw('CONCAT_WS(" ", bi.first_name, bi.last_name) like ?', ["%{$search}%"])
                            ->orWhere('bf.household_name', 'like', "%{$search}%")
                            ->orWhere('bo.name', 'like', "%{$search}%")
                            ->orWhere('bsi.item_name_snapshot', 'like', "%{$search}%");
                    });
                }
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildSupportBaseQuery(Campaign $campaign, array $filters): Builder
    {
        $query = DB::table('beneficiary_supports as bs')
            ->join('beneficiaries as b', 'b.id', '=', 'bs.beneficiary_id')
            ->where('bs.campaign_id', $campaign->id);

        if (! empty($filters['aid_item_id'])) {
            $query->whereExists(function ($subQuery) use ($filters) {
                $subQuery->selectRaw('1')
                    ->from('beneficiary_support_items as bsi')
                    ->whereColumn('bsi.beneficiary_support_id', 'bs.id')
                    ->where('bsi.aid_item_id', (int) $filters['aid_item_id']);
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('bs.supported_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('bs.supported_at', '<=', $filters['to']);
        }

        if (! empty($filters['status'])) {
            $query->where('bs.status', $filters['status']);
        }

        if (! empty($filters['beneficiary_type'])) {
            $query->where('b.type', $filters['beneficiary_type']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildLineBaseQuery(Campaign $campaign, array $filters): Builder
    {
        $query = DB::table('beneficiary_support_items as bsi')
            ->join('beneficiary_supports as bs', 'bs.id', '=', 'bsi.beneficiary_support_id')
            ->where('bs.campaign_id', $campaign->id);

        if (! empty($filters['from'])) {
            $query->whereDate('bs.supported_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('bs.supported_at', '<=', $filters['to']);
        }

        if (! empty($filters['status'])) {
            $query->where('bs.status', $filters['status']);
        }

        if (! empty($filters['aid_item_id'])) {
            $query->where('bsi.aid_item_id', (int) $filters['aid_item_id']);
        }

        return $query;
    }
}
