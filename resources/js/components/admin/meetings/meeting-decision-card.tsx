import { router } from '@inertiajs/react'
import { ArrowDown, ArrowUp, Pencil, Trash2 } from 'lucide-react'
import { DecisionStatusBadge } from '@/components/admin/meetings/decision-status-badge'
import { PriorityBadge } from '@/components/admin/meetings/priority-badge'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { cn } from '@/lib/utils'
import type { DecisionStatus, MeetingDecision, SelectOption } from '@/types/models/meeting'

type Props = {
  decision: MeetingDecision
  meetingId: number
  statusOptions: SelectOption[]
  onEdit?: (decision: MeetingDecision) => void
  onMoveUp?: () => void
  onMoveDown?: () => void
  canEdit?: boolean
}

export function MeetingDecisionCard({
  decision,
  meetingId,
  statusOptions,
  onEdit,
  onMoveUp,
  onMoveDown,
  canEdit = true,
}: Props) {
  const updateStatus = (status: DecisionStatus) => {
    router.patch(
      route('admin.meetings.decisions.updateStatus', [meetingId, decision.id]),
      { status },
      { preserveScroll: true },
    )
  }

  const destroy = () => {
    if (!confirm('Delete this decision?')) {
      return
    }

    router.delete(
      route('admin.meetings.decisions.destroy', [meetingId, decision.id]),
      { preserveScroll: true },
    )
  }

  return (
    <div
      className={cn(
        'space-y-3 rounded-lg border p-4',
        decision.is_overdue && 'border-rose-300 bg-rose-50/40',
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <p className="text-muted-foreground text-xs font-medium">
            {decision.decision_number}
          </p>
          <h4 className="font-semibold">{decision.title}</h4>
        </div>
        <div className="flex flex-wrap gap-2">
          <BadgeLike label={decision.decision_type_label ?? decision.decision_type} />
          <DecisionStatusBadge
            status={decision.status}
            label={decision.status_label}
          />
          <PriorityBadge
            priority={decision.priority}
            label={decision.priority_label}
          />
        </div>
      </div>

      <p className="text-muted-foreground text-sm whitespace-pre-wrap">
        {decision.description}
      </p>

      <div className="text-muted-foreground flex flex-wrap gap-4 text-sm">
        {decision.assigned_to && <span>Assigned: {decision.assigned_to}</span>}
        {decision.due_date && (
          <span className={decision.is_overdue ? 'font-medium text-rose-700' : ''}>
            Due: {decision.due_date}
            {decision.is_overdue ? ' (Overdue)' : ''}
          </span>
        )}
      </div>

      {canEdit && (
        <div className="flex flex-wrap items-center gap-2">
          <Select
            value={decision.status}
            onValueChange={(value) => updateStatus(value as DecisionStatus)}
          >
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              {statusOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          {onEdit && (
            <Button variant="outline" size="sm" onClick={() => onEdit(decision)}>
              <Pencil className="size-4" />
            </Button>
          )}

          {onMoveUp && (
            <Button variant="outline" size="sm" onClick={onMoveUp}>
              <ArrowUp className="size-4" />
            </Button>
          )}

          {onMoveDown && (
            <Button variant="outline" size="sm" onClick={onMoveDown}>
              <ArrowDown className="size-4" />
            </Button>
          )}

          <Button variant="outline" size="sm" onClick={destroy}>
            <Trash2 className="size-4 text-rose-600" />
          </Button>
        </div>
      )}
    </div>
  )
}

function BadgeLike({ label }: { label: string }) {
  return (
    <span className="bg-muted inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize">
      {label.replaceAll('_', ' ')}
    </span>
  )
}
