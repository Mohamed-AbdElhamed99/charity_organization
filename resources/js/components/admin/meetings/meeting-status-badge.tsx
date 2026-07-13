import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { MeetingStatus } from '@/types/models/meeting'

const statusClasses: Record<MeetingStatus, string> = {
  scheduled: 'border-sky-300 text-sky-700 bg-sky-50',
  in_progress: 'border-amber-300 text-amber-700 bg-amber-50',
  completed: 'border-emerald-300 text-emerald-700 bg-emerald-50',
  cancelled: 'border-rose-300 text-rose-700 bg-rose-50',
  postponed: 'border-slate-300 text-slate-700 bg-slate-50',
}

const statusLabels: Record<MeetingStatus, string> = {
  scheduled: 'Scheduled',
  in_progress: 'In Progress',
  completed: 'Completed',
  cancelled: 'Cancelled',
  postponed: 'Postponed',
}

type Props = {
  status: MeetingStatus
  label?: string
  className?: string
}

export function MeetingStatusBadge({ status, label, className }: Props) {
  return (
    <Badge
      variant="outline"
      className={cn('capitalize', statusClasses[status], className)}
    >
      {label ?? statusLabels[status] ?? status}
    </Badge>
  )
}
