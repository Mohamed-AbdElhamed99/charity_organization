<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionDirection;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();

        $stats = [
            'active_campaigns_count' => Campaign::query()->where('status', 'active')->count(),
        ];

        if ($request->user()?->can('view_transactions')) {
            [$stats, $monthlySummary] = $this->buildFinancialStats($stats, $now);

            return Inertia::render('admin/dashboard', [
                'stats' => $stats,
                'monthly_summary' => $monthlySummary,
            ]);
        }

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Compute financial stats and monthly chart data.
     *
     * @param  array<string, mixed>  $stats
     * @return array{0: array<string, mixed>, 1: list<array{month: string, donations: int, expenses: int}>}
     */
    private function buildFinancialStats(array $stats, Carbon $now): array
    {
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = $now->copy()->endOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $donationTypeId = TransactionType::idForCode(TransactionType::CODE_DONATION);
        $directionIn = TransactionDirection::In->value;
        $campaignExpenseTypeId = TransactionType::idForCode(TransactionType::CODE_CAMPAIGN_EXPENSE);
        $generalExpenseTypeId = TransactionType::idForCode(TransactionType::CODE_GENERAL_EXPENSE);
        $directionOut = TransactionDirection::Out->value;

        $thisMonthDonations = Transaction::query()
            ->where('transaction_type_id', $donationTypeId)
            ->where('direction', $directionIn)
            ->whereBetween('transaction_date', [$thisMonthStart, $thisMonthEnd])
            ->sum('net_amount');

        $lastMonthDonations = Transaction::query()
            ->where('transaction_type_id', $donationTypeId)
            ->where('direction', $directionIn)
            ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('net_amount');

        $latestRunningBalance = Transaction::query()
            ->whereNotNull('running_balance')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance');

        $stats['total_donations_this_month'] = [
            'cents' => Money::decimalToCents((float) ($thisMonthDonations ?? 0)),
            'prior_month_cents' => Money::decimalToCents((float) ($lastMonthDonations ?? 0)),
        ];

        $stats['net_balance'] = [
            'cents' => Money::decimalToCents((float) ($latestRunningBalance ?? 0)),
        ];

        $monthlySummary = $this->buildMonthlySummary(
            $now,
            $donationTypeId,
            $directionIn,
            $campaignExpenseTypeId,
            $generalExpenseTypeId,
            $directionOut,
        );

        return [$stats, $monthlySummary];
    }

    /**
     * Build a 12-month donations vs. expenses breakdown, filling months with no data as zero.
     *
     * @return list<array{month: string, donations: int, expenses: int}>
     */
    private function buildMonthlySummary(
        Carbon $now,
        int $donationTypeId,
        string $directionIn,
        int $campaignExpenseTypeId,
        int $generalExpenseTypeId,
        string $directionOut,
    ): array {
        $twelveMonthsAgo = $now->copy()->subMonths(11)->startOfMonth()->toDateString();

        $isMySQL = DB::connection()->getDriverName() !== 'sqlite';
        $monthExpr = $isMySQL
            ? "DATE_FORMAT(transaction_date, '%Y-%m')"
            : "strftime('%Y-%m', transaction_date)";

        $rows = Transaction::query()
            ->where('transaction_date', '>=', $twelveMonthsAgo)
            ->selectRaw("{$monthExpr} as month")
            ->selectRaw(
                'SUM(CASE WHEN transaction_type_id = ? AND direction = ? THEN net_amount ELSE 0 END) as donations_decimal',
                [$donationTypeId, $directionIn],
            )
            ->selectRaw(
                'SUM(CASE WHEN transaction_type_id IN (?, ?) AND direction = ? THEN net_amount ELSE 0 END) as expenses_decimal',
                [$campaignExpenseTypeId, $generalExpenseTypeId, $directionOut],
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return collect(range(0, 11))
            ->map(function (int $offset) use ($now, $rows): array {
                $month = $now->copy()->subMonths(11 - $offset)->format('Y-m');
                $row = $rows->get($month);

                return [
                    'month' => $month,
                    'donations' => $row ? Money::decimalToCents((float) $row->donations_decimal) : 0,
                    'expenses' => $row ? Money::decimalToCents((float) $row->expenses_decimal) : 0,
                ];
            })
            ->values()
            ->all();
    }
}
