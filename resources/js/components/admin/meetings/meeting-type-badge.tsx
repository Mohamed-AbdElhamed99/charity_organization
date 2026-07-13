import { Badge } from '@/components/ui/badge'
import type { MeetingType } from '@/types/models/meeting'

const typeLabels: Record<MeetingType, string> = {
  board: 'Board',
  committee: 'Committee',
  general_assembly: 'General Assembly',
  field: 'Field',
  emergency: 'Emergency',
  other: 'Other',
}

type Props = {
  type: MeetingType
  label?: string
}

export function MeetingTypeBadge({ type, label }: Props) {
  return (
    <Badge variant="secondary" className="capitalize">
      {label ?? typeLabels[type] ?? type}
    </Badge>
  )
}
