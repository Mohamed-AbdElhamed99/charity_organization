import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { DecisionPriority } from '@/types/models/meeting'

const priorityClasses: Record<DecisionPriority, string> = {
  low: 'border-slate-300 text-slate-600 bg-slate-50',
  medium: 'border-blue-300 text-blue-700 bg-blue-50',
  high: 'border-orange-300 text-orange-700 bg-orange-50',
  critical: 'border-red-300 text-red-700 bg-red-50',
}

const priorityLabels: Record<DecisionPriority, string> = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  critical: 'Critical',
}

type Props = {
  priority: DecisionPriority
  label?: string
}

export function PriorityBadge({ priority, label }: Props) {
  return (
    <Badge
      variant="outline"
      className={cn('capitalize', priorityClasses[priority])}
    >
      {label ?? priorityLabels[priority] ?? priority}
    </Badge>
  )
}
