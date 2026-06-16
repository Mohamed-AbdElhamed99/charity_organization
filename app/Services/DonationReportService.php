<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DonationReportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters): Builder
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;
        $status = $filters['status'] ?? null;
        $currencyId = $filters['currency'] ?? null;
        $donorCoversFee = $filters['donor_covers_fee'] ?? null;
        $type = $filters['type'] ?? null;
        $donorSearch = $filters['donor'] ?? null;
        $amountMin = $filters['amount_min'] ?? null;
        $amountMax = $filters['amount_max'] ?? null;

        return Donation::query()
            ->with(['donor', 'campaign', 'transaction.currency'])
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($campaignId, fn ($q) => $q->where('campaign_id', (int) $campaignId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($currencyId, function ($q) use ($currencyId) {
                $q->whereHas('transaction', fn ($tq) => $tq->where('currency_id', (int) $currencyId));
            })
            ->when($donorCoversFee !== null && $donorCoversFee !== '', function ($q) use ($donorCoversFee) {
                $q->where('donor_covers_fee', filter_var($donorCoversFee, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($type === 'general', fn ($q) => $q->where('is_general', true))
            ->when($type === 'campaign', fn ($q) => $q->where('is_general', false)->whereNotNull('campaign_id'))
            ->when($donorSearch, function ($q) use ($donorSearch) {
                $q->whereHas('donor', function ($uq) use ($donorSearch) {
                    $uq->where('name', 'like', "%{$donorSearch}%")
                        ->orWhere('email', 'like', "%{$donorSearch}%");
                });
            })
            ->when($amountMin, fn ($q) => $q->where('amount', '>=', (int) $amountMin))
            ->when($amountMax, fn ($q) => $q->where('amount', '<=', (int) $amountMax));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function summarize(array $filters): array
    {
        $base = $this->query($filters)->where('status', DonationStatus::Succeeded);

        $totals = (clone $base)
            ->join('transactions', 'donations.transaction_id', '=', 'transactions.id')
            ->selectRaw('COUNT(donations.id) as donation_count')
            ->selectRaw('COALESCE(SUM(donations.amount), 0) as total_gift_cents')
            ->selectRaw('COALESCE(SUM(transactions.gross_amount), 0) as total_gross_decimal')
            ->selectRaw('COALESCE(SUM(transactions.fee_amount), 0) as total_fee_decimal')
            ->selectRaw('COALESCE(SUM(transactions.net_amount), 0) as total_net_decimal')
            ->first();

        $byCampaign = (clone $base)
            ->select('campaign_id')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_gift_cents')
            ->groupBy('campaign_id')
            ->with('campaign:id,title_en,title_ar')
            ->get();

        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? 'strftime("%Y-%m", donations.created_at)'
            : 'DATE_FORMAT(donations.created_at, "%Y-%m")';

        $byMonth = (clone $base)
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_gift_cents')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'donation_count' => (int) $totals->donation_count,
            'total_gift_cents' => (int) $totals->total_gift_cents,
            'total_gross_cents' => Money::decimalToCents($totals->total_gross_decimal),
            'total_fee_cents' => Money::decimalToCents($totals->total_fee_decimal),
            'total_net_cents' => Money::decimalToCents($totals->total_net_decimal),
            'by_campaign' => $byCampaign,
            'by_month' => $byMonth,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getExportRows(array $filters): Collection
    {
        return $this->query($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function countForExport(array $filters): int
    {
        return $this->query($filters)->count();
    }
}
