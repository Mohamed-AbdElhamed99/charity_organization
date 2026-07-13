import { Head, Link, router, usePage } from '@inertiajs/react'
import {
  CalendarDays,
  CheckCircle2,
  ClipboardList,
  Plus,
  Trash2,
} from 'lucide-react'
import { MeetingCard } from '@/components/admin/meetings/meeting-card'
import { MeetingStatusBadge } from '@/components/admin/meetings/meeting-status-badge'
import { MeetingTypeBadge } from '@/components/admin/meetings/meeting-type-badge'
import { DataTablePagination } from '@/components/data-table/pagination'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import type { Paginated } from '@/types/pagination'
import type {
  MeetingFilters,
  MeetingListItem,
  MeetingStatistics,
  SelectOption,
} from '@/types/models/meeting'

type PageProps = {
  meetings: Paginated<MeetingListItem>
  filters: MeetingFilters
  statistics: MeetingStatistics
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  campaignOptions: SelectOption[]
}

export default function MeetingsIndex() {
  const {
    meetings,
    filters,
    statistics,
    typeOptions,
    statusOptions,
    campaignOptions,
  } = usePage<PageProps>().props

  const applyFilters = (next: Partial<MeetingFilters>) => {
    router.get(
      route('admin.meetings.index'),
      { ...filters, ...next, page: 1 },
      { preserveState: true, replace: true },
    )
  }

  const destroy = (id: number) => {
    if (!confirm('Delete this meeting?')) {
      return
    }
    router.delete(route('admin.meetings.destroy', id))
  }

  return (
    <>
      <Head title="Meetings" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-end justify-between gap-3">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Meetings</h2>
            <p className="text-muted-foreground">
              Schedule meetings, record minutes, and track decisions.
            </p>
          </div>
          <Button asChild>
            <Link href={route('admin.meetings.create')}>
              <Plus className="me-2 size-4" />
              New meeting
            </Link>
          </Button>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard
            title="Total meetings"
            value={statistics.total}
            icon={<CalendarDays className="size-4" />}
          />
          <StatCard
            title="Completed"
            value={statistics.by_status.completed ?? 0}
            icon={<CheckCircle2 className="size-4" />}
          />
          <StatCard
            title="Upcoming"
            value={statistics.upcoming_count}
            icon={<CalendarDays className="size-4" />}
          />
          <StatCard
            title="Pending decisions"
            value={statistics.decisions_pending}
            icon={<ClipboardList className="size-4" />}
          />
        </div>

        <div className="grid gap-3 rounded-lg border p-4 md:grid-cols-6">
          <Input
            placeholder="Search meetings…"
            defaultValue={filters.query ?? filters.search ?? ''}
            onChange={(e) => {
              const value = e.target.value
              window.clearTimeout((window as unknown as { __meetingSearch?: number }).__meetingSearch)
              ;(window as unknown as { __meetingSearch?: number }).__meetingSearch =
                window.setTimeout(() => applyFilters({ query: value }), 300)
            }}
          />
          <Select
            value={filters.type ?? 'all'}
            onValueChange={(value) =>
              applyFilters({ type: value === 'all' ? undefined : value })
            }
          >
            <SelectTrigger>
              <SelectValue placeholder="Type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All types</SelectItem>
              {typeOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Select
            value={filters.status ?? 'all'}
            onValueChange={(value) =>
              applyFilters({ status: value === 'all' ? undefined : value })
            }
          >
            <SelectTrigger>
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All statuses</SelectItem>
              {statusOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Input
            type="date"
            value={filters.date_from ?? ''}
            onChange={(e) => applyFilters({ date_from: e.target.value || undefined })}
          />
          <Input
            type="date"
            value={filters.date_to ?? ''}
            onChange={(e) => applyFilters({ date_to: e.target.value || undefined })}
          />
          <Select
            value={filters.campaign_id?.toString() ?? 'all'}
            onValueChange={(value) =>
              applyFilters({
                campaign_id: value === 'all' ? undefined : value,
              })
            }
          >
            <SelectTrigger>
              <SelectValue placeholder="Campaign" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All campaigns</SelectItem>
              {campaignOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="hidden md:block">
          <div className="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Number</TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Attendees</TableHead>
                  <TableHead>Decisions</TableHead>
                  <TableHead>Campaigns</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {meetings.data.length === 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={9}
                      className="text-muted-foreground text-center"
                    >
                      No meetings found.
                    </TableCell>
                  </TableRow>
                ) : (
                  meetings.data.map((meeting) => (
                    <TableRow key={meeting.id}>
                      <TableCell className="font-mono text-xs">
                        {meeting.meeting_number}
                      </TableCell>
                      <TableCell className="font-medium">
                        <Link
                          href={route('admin.meetings.show', meeting.id)}
                          className="hover:underline"
                        >
                          {meeting.title}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <MeetingTypeBadge
                          type={meeting.type}
                          label={meeting.type_label}
                        />
                      </TableCell>
                      <TableCell>
                        <MeetingStatusBadge
                          status={meeting.status}
                          label={meeting.status_label}
                        />
                      </TableCell>
                      <TableCell>
                        {meeting.formatted_date ?? meeting.meeting_date}
                      </TableCell>
                      <TableCell>{meeting.attendees_count ?? 0}</TableCell>
                      <TableCell>{meeting.decisions_count ?? 0}</TableCell>
                      <TableCell>{meeting.campaigns?.length ?? 0}</TableCell>
                      <TableCell>
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" asChild>
                            <Link href={route('admin.meetings.show', meeting.id)}>
                              View
                            </Link>
                          </Button>
                          <Button variant="ghost" size="sm" asChild>
                            <Link href={route('admin.meetings.edit', meeting.id)}>
                              Edit
                            </Link>
                          </Button>
                          <Button variant="ghost" size="sm" asChild>
                            <a
                              href={route('admin.meetings.print', meeting.id)}
                              target="_blank"
                              rel="noreferrer"
                            >
                              Print
                            </a>
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => destroy(meeting.id)}
                          >
                            <Trash2 className="size-4 text-rose-600" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>

        <div className="grid gap-3 md:hidden">
          {meetings.data.map((meeting) => (
            <MeetingCard key={meeting.id} meeting={meeting} />
          ))}
        </div>

        <DataTablePagination
          pagination={{
            current_page: meetings.current_page,
            last_page: meetings.last_page,
            per_page: meetings.per_page,
          }}
          search={filters as Record<string, unknown>}
          indexUrl={route('admin.meetings.index')}
          defaultPerPage={20}
        />
      </Main>
    </>
  )
}

function StatCard({
  title,
  value,
  icon,
}: {
  title: string
  value: number
  icon: React.ReactNode
}) {
  return (
    <div className="rounded-lg border p-4">
      <div className="text-muted-foreground mb-2 flex items-center gap-2 text-sm">
        {icon}
        {title}
      </div>
      <div className="text-2xl font-bold">{value}</div>
    </div>
  )
}

MeetingsIndex.layout = {
  breadcrumbs: [{ title: 'Meetings', href: '/admin/meetings' }],
}
