import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { DecisionStatus } from '@/types/models/meeting'

const statusClasses: Record<DecisionStatus, string> = {
  pending: 'border-slate-300 text-slate-700 bg-slate-50',
  in_progress: 'border-sky-300 text-sky-700 bg-sky-50',
  completed: 'border-emerald-300 text-emerald-700 bg-emerald-50',
  cancelled: 'border-rose-300 text-rose-700 bg-rose-50',
  deferred: 'border-amber-300 text-amber-700 bg-amber-50',
}

const statusLabels: Record<DecisionStatus, string> = {
  pending: 'Pending',
  in_progress: 'In Progress',
  completed: 'Completed',
  cancelled: 'Cancelled',
  deferred: 'Deferred',
}

type Props = {
  status: DecisionStatus
  label?: string
}

export function DecisionStatusBadge({ status, label }: Props) {
  return (
    <Badge
      variant="outline"
      className={cn('capitalize', statusClasses[status])}
    >
      {label ?? statusLabels[status] ?? status}
    </Badge>
  )
}
