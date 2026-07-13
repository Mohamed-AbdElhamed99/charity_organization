import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import type { MeetingAttendee } from '@/types/models/meeting'

type Props = {
  attendees: {
    data:MeetingAttendee[]
  }
}

export function AttendeesTable({ attendeesProp }: any) {
  const attendees = attendeesProp?.data;
  const stats = {
    invited: attendees?.filter((a) => a.attendance_status === 'invited').length,
    confirmed: attendees?.filter((a) => a.attendance_status === 'confirmed')
      .length,
    attended: attendees?.filter((a) => a.attendance_status === 'attended')
      .length,
    absent: attendees?.filter((a) => a.attendance_status === 'absent').length,
  }
  
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-2 text-sm">
        <Badge variant="outline">Invited {stats.invited}</Badge>
        <Badge variant="outline">Confirmed {stats.confirmed}</Badge>
        <Badge variant="outline">Attended {stats.attended}</Badge>
        <Badge variant="outline">Absent {stats.absent}</Badge>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>#</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Title</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Attendance</TableHead>
              <TableHead>Email</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {attendees?.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={6}
                  className="text-muted-foreground text-center"
                >
                  No attendees yet.
                </TableCell>
              </TableRow>
            ) : (
              attendees?.map((attendee, index) => (
                <TableRow key={attendee.id ?? `${attendee.name}-${index}`}>
                  <TableCell>{index + 1}</TableCell>
                  <TableCell className="font-medium">{attendee.name}</TableCell>
                  <TableCell>{attendee.title ?? '—'}</TableCell>
                  <TableCell>
                    <Badge variant="secondary">
                      {attendee.role_label ?? attendee.role}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {attendee.attendance_status_label ??
                        attendee.attendance_status}
                    </Badge>
                  </TableCell>
                  <TableCell>{attendee.email ?? '—'}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}
