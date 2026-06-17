import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'

interface StatCardDelta {
    percentage: number
    isPositive: boolean
}

interface StatCardProps {
    label: string
    value: string
    description?: string
    delta?: StatCardDelta
}

function DeltaBadge({ delta }: { delta: StatCardDelta }) {
    const arrow = delta.isPositive ? '▲' : '▼'
    const pct = Math.abs(delta.percentage)
    const formatted = pct === Infinity ? 'New' : `${pct.toFixed(1)}%`

    return (
        <span
            className={cn(
                'inline-flex items-center gap-0.5 text-xs font-medium',
                delta.isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive',
            )}
        >
            {arrow} {formatted}
            <span className="text-muted-foreground font-normal"> vs last month</span>
        </span>
    )
}

export function StatCard({ label, value, description, delta }: StatCardProps) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-muted-foreground text-sm font-medium">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-1">
                <p className="text-2xl font-bold tabular-nums">{value}</p>
                {delta && <DeltaBadge delta={delta} />}
                {description && (
                    <p className="text-muted-foreground text-xs">{description}</p>
                )}
            </CardContent>
        </Card>
    )
}
