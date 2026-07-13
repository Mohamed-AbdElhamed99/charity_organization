import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { MeetingForm } from '@/components/admin/meetings/meeting-form'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import type { SelectOption } from '@/types/models/meeting'

type PageProps = {
  campaignOptions: SelectOption[]
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  locationTypeOptions: SelectOption[]
  attendeeRoleOptions: SelectOption[]
  attendanceStatusOptions: SelectOption[]
}

export default function MeetingsCreate() {
  const props = usePage<PageProps>().props

  return (
    <>
      <Head title="New Meeting" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.meetings.index')}>
              <ArrowLeft className="me-2 size-4" />
              Back to meetings
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">New meeting</h2>
          <p className="text-muted-foreground">
            Create a meeting with attendees and linked campaigns.
          </p>
        </div>

        <MeetingForm
          {...props}
          submitUrl={route('admin.meetings.store')}
          method="post"
          submitLabel="Save & view"
        />
      </Main>
    </>
  )
}

MeetingsCreate.layout = {
  breadcrumbs: [
    { title: 'Meetings', href: '/admin/meetings' },
    { title: 'Create', href: '/admin/meetings/create' },
  ],
}
