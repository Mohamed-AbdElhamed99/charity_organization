import { Head, usePage } from '@inertiajs/react'
import { MonthlyChart } from '@/components/admin/dashboard/monthly-chart'
import { StatCard } from '@/components/admin/dashboard/stat-card'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatCurrency } from '@/lib/utils'
import type { DashboardStats, MonthlySummaryEntry } from '@/types/models/dashboard'
import { dashboard } from '@/routes/admin'

type PageProps = {
    stats: DashboardStats
    monthly_summary?: MonthlySummaryEntry[]
}

function computeDelta(cents: number, priorCents: number) {
    if (priorCents === 0) {
        return { percentage: cents > 0 ? Infinity : 0, isPositive: cents >= 0 }
    }
    const pct = ((cents - priorCents) / priorCents) * 100
    return { percentage: pct, isPositive: pct >= 0 }
}

export default function Dashboard() {
    const { stats, monthly_summary } = usePage<PageProps>().props

    const hasFinancials =
        stats.total_donations_this_month !== undefined ||
        stats.net_balance !== undefined

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* ── Stat cards row ─────────────────────────────────────── */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {hasFinancials && stats.total_donations_this_month && (
                        <StatCard
                            label="Donations This Month"
                            value={formatCurrency(stats.total_donations_this_month.cents)}
                            delta={computeDelta(
                                stats.total_donations_this_month.cents,
                                stats.total_donations_this_month.prior_month_cents,
                            )}
                        />
                    )}

                    {hasFinancials && stats.net_balance && (
                        <StatCard
                            label="Net Balance"
                            value={formatCurrency(stats.net_balance.cents)}
                        />
                    )}

                    <StatCard
                        label="Active Campaigns"
                        value={String(stats.active_campaigns_count)}
                    />
                </div>

                {/* ── Monthly chart panel ─────────────────────────────────── */}
                {monthly_summary ? (
                    <Card className="min-h-72 flex-1">
                        <CardHeader>
                            <CardTitle className="text-base font-semibold">
                                Donations vs. Expenses — Last 12 Months
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="h-64">
                            <MonthlyChart data={monthly_summary} />
                        </CardContent>
                    </Card>
                ) : (
                    <div className="text-muted-foreground flex flex-1 items-center justify-center rounded-xl border border-dashed py-16 text-sm">
                        You don&rsquo;t have permission to view financial summaries.
                    </div>
                )}
            </div>
        </>
    )
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
}
