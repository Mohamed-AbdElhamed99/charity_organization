import { Link } from '@inertiajs/react'
import { Eye, Pencil, Printer } from 'lucide-react'
import { MeetingStatusBadge } from '@/components/admin/meetings/meeting-status-badge'
import { MeetingTypeBadge } from '@/components/admin/meetings/meeting-type-badge'
import { Button } from '@/components/ui/button'
import type { MeetingListItem } from '@/types/models/meeting'

type Props = {
  meeting: MeetingListItem
}

export function MeetingCard({ meeting }: Props) {
  return (
    <div className="space-y-3 rounded-lg border p-4">
      <div className="flex items-start justify-between gap-2">
        <div>
          <p className="text-muted-foreground text-xs font-medium">
            {meeting.meeting_number}
          </p>
          <h3 className="font-semibold">{meeting.title}</h3>
        </div>
        <MeetingStatusBadge
          status={meeting.status}
          label={meeting.status_label}
        />
      </div>

      <div className="flex flex-wrap gap-2">
        <MeetingTypeBadge type={meeting.type} label={meeting.type_label} />
        <span className="text-muted-foreground text-sm">
          {meeting.formatted_date ?? meeting.meeting_date}
        </span>
      </div>

      <div className="text-muted-foreground flex gap-4 text-sm">
        <span>{meeting.attendees_count ?? 0} attendees</span>
        <span>{meeting.decisions_count ?? 0} decisions</span>
        <span>{meeting.campaigns?.length ?? 0} campaigns</span>
      </div>

      <div className="flex gap-2">
        <Button variant="outline" size="sm" asChild>
          <Link href={route('admin.meetings.show', meeting.id)}>
            <Eye className="size-4" />
          </Link>
        </Button>
        <Button variant="outline" size="sm" asChild>
          <Link href={route('admin.meetings.edit', meeting.id)}>
            <Pencil className="size-4" />
          </Link>
        </Button>
        <Button variant="outline" size="sm" asChild>
          <a
            href={route('admin.meetings.print', meeting.id)}
            target="_blank"
            rel="noreferrer"
          >
            <Printer className="size-4" />
          </a>
        </Button>
      </div>
    </div>
  )
}
