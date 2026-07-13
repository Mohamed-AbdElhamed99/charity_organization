import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { MeetingForm } from '@/components/admin/meetings/meeting-form'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import type { Meeting, SelectOption } from '@/types/models/meeting'

type PageProps = {
  meeting: Meeting
  campaignOptions: SelectOption[]
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  locationTypeOptions: SelectOption[]
  attendeeRoleOptions: SelectOption[]
  attendanceStatusOptions: SelectOption[]
}

export default function MeetingsEdit() {
  const { meeting, ...options } = usePage<PageProps>().props

  return (
    <>
      <Head title={`Edit ${meeting.title}`} />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.meetings.show', meeting.id)}>
              <ArrowLeft className="me-2 size-4" />
              Back to meeting
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">Edit meeting</h2>
          <p className="text-muted-foreground">
            {meeting.meeting_number} · {meeting.title}
          </p>
        </div>

        <MeetingForm
          meeting={meeting}
          {...options}
          submitUrl={route('admin.meetings.update', meeting.id)}
          method="put"
          submitLabel="Save changes"
        />
      </Main>
    </>
  )
}

MeetingsEdit.layout = {
  breadcrumbs: [
    { title: 'Meetings', href: '/admin/meetings' },
    { title: 'Edit', href: '#' },
  ],
}
