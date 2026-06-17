import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts'
import { formatCurrency } from '@/lib/utils'
import type { MonthlySummaryEntry } from '@/types/models/dashboard'

interface MonthlyChartProps {
    data: MonthlySummaryEntry[]
}

function formatMonthLabel(yearMonth: string): string {
    const [year, month] = yearMonth.split('-')
    const date = new Date(Number(year), Number(month) - 1, 1)
    return date.toLocaleString('en', { month: 'short' })
}

function formatYAxisTick(value: number): string {
    if (value === 0) { return '0' }
    if (Math.abs(value) >= 100_000_00) {
        return `${(value / 100_000_00).toFixed(1)}M`
    }
    if (Math.abs(value) >= 1_000_00) {
        return `${(value / 1_000_00).toFixed(0)}k`
    }
    return formatCurrency(value)
}

interface TooltipPayloadEntry {
    name: string
    value: number
    color: string
}

interface CustomTooltipProps {
    active?: boolean
    payload?: TooltipPayloadEntry[]
    label?: string
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
    if (!active || !payload || payload.length === 0) {
        return null
    }

    return (
        <div className="bg-popover text-popover-foreground rounded-lg border p-3 shadow-md">
            <p className="mb-2 text-sm font-semibold">{label}</p>
            {payload.map((entry) => (
                <div key={entry.name} className="flex items-center gap-2 text-xs">
                    <span
                        className="inline-block size-2.5 rounded-sm"
                        style={{ backgroundColor: entry.color }}
                    />
                    <span className="text-muted-foreground capitalize">{entry.name}:</span>
                    <span className="font-medium tabular-nums">{formatCurrency(entry.value)}</span>
                </div>
            ))}
        </div>
    )
}

export function MonthlyChart({ data }: MonthlyChartProps) {
    const chartData = data.map((entry) => ({
        ...entry,
        label: formatMonthLabel(entry.month),
    }))

    return (
        <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                <CartesianGrid
                    vertical={false}
                    stroke="var(--color-border)"
                    strokeDasharray="3 3"
                />
                <XAxis
                    dataKey="label"
                    tick={{ fontSize: 12, fill: 'var(--color-muted-foreground)' }}
                    axisLine={false}
                    tickLine={false}
                />
                <YAxis
                    tickFormatter={formatYAxisTick}
                    tick={{ fontSize: 11, fill: 'var(--color-muted-foreground)' }}
                    axisLine={false}
                    tickLine={false}
                    width={56}
                />
                <Tooltip
                    content={<CustomTooltip />}
                    cursor={{ fill: 'var(--color-muted)', opacity: 0.4 }}
                />
                <Bar
                    dataKey="donations"
                    name="Donations"
                    fill="var(--color-chart-1)"
                    radius={[3, 3, 0, 0]}
                    maxBarSize={28}
                />
                <Bar
                    dataKey="expenses"
                    name="Expenses"
                    fill="var(--color-chart-2)"
                    radius={[3, 3, 0, 0]}
                    maxBarSize={28}
                />
            </BarChart>
        </ResponsiveContainer>
    )
}
