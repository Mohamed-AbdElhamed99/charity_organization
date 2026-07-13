import { useMemo, useState } from 'react'
import { Head, Link, router, useForm, usePage } from '@inertiajs/react'
import {
  ArrowLeft,
  CheckCircle2,
  Pencil,
  Plus,
  Printer,
} from 'lucide-react'
import { AttendeesTable } from '@/components/admin/meetings/attendees-table'
import { MeetingAttachmentsPanel } from '@/components/admin/meetings/meeting-attachments-panel'
import { MeetingDecisionCard } from '@/components/admin/meetings/meeting-decision-card'
import { MeetingStatusBadge } from '@/components/admin/meetings/meeting-status-badge'
import { MeetingTypeBadge } from '@/components/admin/meetings/meeting-type-badge'
import InputError from '@/components/input-error'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { cn } from '@/lib/utils'
import type {
  Meeting,
  MeetingDecision,
  SelectOption,
} from '@/types/models/meeting'
import { route } from 'ziggy-js'

type TabKey =
  | 'info'
  | 'attendees'
  | 'minutes'
  | 'decisions'
  | 'attachments'
  | 'print'

type PageProps = {
  meeting: Meeting
  can: {
    update: boolean
    delete: boolean
    approveMinutes: boolean
  }
  decisionTypeOptions: SelectOption[]
  decisionStatusOptions: SelectOption[]
  decisionPriorityOptions: SelectOption[]
  minutesFormatOptions: SelectOption[]
  minutesLanguageOptions: SelectOption[]
}

const tabs: { key: TabKey; label: string }[] = [
  { key: 'info', label: 'Meeting info' },
  { key: 'attendees', label: 'Attendees' },
  { key: 'minutes', label: 'Minutes' },
  { key: 'decisions', label: 'Decisions' },
  { key: 'attachments', label: 'Attachments' },
  { key: 'print', label: 'Print report' },
]

export default function MeetingsShow() {
  const {
    meeting,
    can,
    decisionTypeOptions,
    decisionStatusOptions,
    decisionPriorityOptions,
    minutesFormatOptions,
    minutesLanguageOptions,
  } = usePage<PageProps>().props

  const [tab, setTab] = useState<TabKey>('info')
  const [showDecisionForm, setShowDecisionForm] = useState(false)
  const [editingDecision, setEditingDecision] = useState<MeetingDecision | null>(
    null,
  )
  const [showMinutesForm, setShowMinutesForm] = useState(!meeting.minutes)

  const decisionStats = useMemo(() => {
    const decisions = meeting.decisions?.data ?? []
    return {
      pending: decisions?.filter((d) => d.status === 'pending').length,
      in_progress: decisions?.filter((d) => d.status === 'in_progress').length,
      completed: decisions?.filter((d) => d.status === 'completed').length,
      overdue: decisions?.filter((d) => d.is_overdue).length,
    }
  }, [meeting.decisions])

  const minutesForm = useForm({
    content: meeting.minutes?.content ?? '',
    summary: meeting.minutes?.summary ?? '',
    format: meeting.minutes?.format ?? 'standard',
    language: meeting.minutes?.language ?? 'en',
    is_approved: meeting.minutes?.is_approved ?? false,
  })

  const decisionForm = useForm({
    title: '',
    description: '',
    decision_type: 'resolution',
    status: 'pending',
    priority: 'medium',
    assigned_to: '',
    due_date: '',
    completion_date: '',
    completion_notes: '',
  })

  const saveMinutes = (event: React.FormEvent) => {
    event.preventDefault()
    if (meeting.minutes) {
      minutesForm.put(
        route('admin.meetings.minutes.update', [
          meeting.id,
          meeting.minutes.id,
        ]),
        { preserveScroll: true },
      )
      return
    }

    minutesForm.post(route('admin.meetings.minutes.store', meeting.id), {
      preserveScroll: true,
      onSuccess: () => setShowMinutesForm(false),
    })
  }

  const approveMinutes = () => {
    if (!meeting.minutes) {
      return
    }
    router.post(
      route('admin.meetings.minutes.approve', [
        meeting.id,
        meeting.minutes.id,
      ]),
      {},
      { preserveScroll: true },
    )
  }

  const openDecisionCreate = () => {
    setEditingDecision(null)
    decisionForm.setData({
      title: '',
      description: '',
      decision_type: 'resolution',
      status: 'pending',
      priority: 'medium',
      assigned_to: '',
      due_date: '',
      completion_date: '',
      completion_notes: '',
    })
    setShowDecisionForm(true)
  }

  const openDecisionEdit = (decision: MeetingDecision) => {
    setEditingDecision(decision)
    decisionForm.setData({
      title: decision.title,
      description: decision.description,
      decision_type: decision.decision_type,
      status: decision.status,
      priority: decision.priority,
      assigned_to: decision.assigned_to ?? '',
      due_date: decision.due_date ?? '',
      completion_date: decision.completion_date ?? '',
      completion_notes: decision.completion_notes ?? '',
    })
    setShowDecisionForm(true)
  }

  const saveDecision = (event: React.FormEvent) => {
    event.preventDefault()
    if (editingDecision) {
      decisionForm.put(
        route('admin.meetings.decisions.update', [
          meeting.id,
          editingDecision.id,
        ]),
        {
          preserveScroll: true,
          onSuccess: () => {
            setShowDecisionForm(false)
            setEditingDecision(null)
          },
        },
      )
      return
    }

    decisionForm.post(route('admin.meetings.decisions.store', meeting.id), {
      preserveScroll: true,
      onSuccess: () => setShowDecisionForm(false),
    })
  }

  const reorder = (orderedIds: number[]) => {
    router.post(
      route('admin.meetings.decisions.reorder', meeting.id),
      { ordered_ids: orderedIds },
      { preserveScroll: true },
    )
  }

  const moveDecision = (index: number, direction: -1 | 1) => {
    const decisions = [...(meeting.decisions ?? [])]
    const target = index + direction
    if (target < 0 || target >= decisions.length) {
      return
    }
    const temp = decisions[index]
    decisions[index] = decisions[target]
    decisions[target] = temp
    reorder(decisions.map((d) => d.id))
  }

  const exportAttendeesCsv = () => {
    const rows = [
      ['Name', 'Title', 'Role', 'Attendance', 'Email', 'Organization'],
      ...(meeting.attendees ?? []).map((a) => [
        a.name,
        a.title ?? '',
        a.role,
        a.attendance_status,
        a.email ?? '',
        a.organization ?? '',
      ]),
    ]
    const csv = rows
      .map((row) =>
        row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(','),
      )
      .join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${meeting.meeting_number}-attendees.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  return (
    <>
      <Head title={meeting.title} />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="space-y-2">
            <Button variant="outline" size="sm" asChild>
              <Link href={route('admin.meetings.index')}>
                <ArrowLeft className="me-2 size-4" />
                Back
              </Link>
            </Button>
            <div>
              <p className="text-muted-foreground font-mono text-sm">
                {meeting.meeting_number}
              </p>
              <h2 className="text-2xl font-bold tracking-tight">
                {meeting.title}
              </h2>
              <div className="mt-2 flex flex-wrap gap-2">
                <MeetingTypeBadge
                  type={meeting.type}
                  label={meeting.type_label}
                />
                <MeetingStatusBadge
                  status={meeting.status}
                  label={meeting.status_label}
                />
              </div>
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            {can.update && (
              <Button variant="outline" asChild>
                <Link href={route('admin.meetings.edit', meeting.id)}>
                  <Pencil className="me-2 size-4" />
                  Edit
                </Link>
              </Button>
            )}
            <Button variant="outline" asChild>
              <a
                href={route('admin.meetings.print', meeting.id)}
                target="_blank"
                rel="noreferrer"
              >
                <Printer className="me-2 size-4" />
                Print
              </a>
            </Button>
          </div>
        </div>

        <div className="flex flex-wrap gap-2 border-b pb-2">
          {tabs.map((item) => (
            <Button
              key={item.key}
              type="button"
              variant={tab === item.key ? 'default' : 'ghost'}
              size="sm"
              onClick={() => setTab(item.key)}
            >
              {item.label}
            </Button>
          ))}
        </div>

        {tab === 'info' && (
          <div className="grid gap-6 lg:grid-cols-2">
            <InfoBlock label="Date" value={meeting.formatted_date ?? meeting.meeting_date} />
            <InfoBlock
              label="Time"
              value={`${meeting.start_time ?? '—'}${meeting.end_time ? ` – ${meeting.end_time}` : ''}${meeting.duration ? ` (${meeting.duration})` : ''}`}
            />
            <InfoBlock
              label="Location"
              value={`${meeting.location_type_label ?? meeting.location_type}${meeting.location ? ` · ${meeting.location}` : ''}`}
            />
            <InfoBlock label="Meeting link" value={meeting.meeting_link ?? '—'} />
            <InfoBlock label="Chairperson" value={meeting.chairperson ?? '—'} />
            <InfoBlock label="Secretary" value={meeting.secretary ?? '—'} />
            <InfoBlock
              label="Quorum"
              value={
                meeting.quorum_required
                  ? `${meeting.attended_count ?? 0} / ${meeting.quorum_required} ${meeting.quorum_met ? '(Met)' : '(Not met)'}`
                  : 'Not set'
              }
            />
            <div className="lg:col-span-2">
              <InfoBlock label="Agenda" value={meeting.agenda ?? '—'} multiline />
            </div>
            <div className="lg:col-span-2">
              <InfoBlock
                label="Description"
                value={meeting.description ?? '—'}
                multiline
              />
            </div>
            <div className="lg:col-span-2 space-y-2">
              <p className="text-muted-foreground text-sm font-medium">
                Linked campaigns
              </p>
              <div className="flex flex-wrap gap-2">
                {(meeting.campaigns ?? []).length === 0 && (
                  <span className="text-muted-foreground text-sm">None</span>
                )}
                {(meeting.campaigns ?? []).map((campaign) => (
                  <Link
                    key={campaign.id}
                    href={route('admin.campaigns.show', campaign.id)}
                    className="bg-muted rounded-md px-2 py-1 text-sm hover:underline"
                  >
                    {campaign.title_en}
                  </Link>
                ))}
              </div>
            </div>
          </div>
        )}

        {tab === 'attendees' && (
          <div className="space-y-4">
            <div className="flex justify-end">
              <Button variant="outline" size="sm" onClick={exportAttendeesCsv}>
                Export attendees
              </Button>
            </div>
            <AttendeesTable attendeesProp={meeting.attendees ?? []} />
          </div>
        )}

        {tab === 'minutes' && (
          <div className="space-y-4">
            {meeting.minutes && !showMinutesForm ? (
              <div className="space-y-4 rounded-lg border p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="flex flex-wrap gap-2">
                    <span className="text-sm">
                      Version {meeting.minutes.version}
                    </span>
                    <span
                      className={cn(
                        'rounded-md px-2 py-0.5 text-xs font-medium',
                        meeting.minutes.is_approved
                          ? 'bg-emerald-100 text-emerald-800'
                          : 'bg-amber-100 text-amber-800',
                      )}
                    >
                      {meeting.minutes.is_approved
                        ? 'Approved'
                        : 'Pending approval'}
                    </span>
                  </div>
                  <div className="flex gap-2">
                    {can.update && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setShowMinutesForm(true)}
                      >
                        Edit minutes
                      </Button>
                    )}
                    {can.approveMinutes && !meeting.minutes.is_approved && (
                      <Button size="sm" onClick={approveMinutes}>
                        <CheckCircle2 className="me-2 size-4" />
                        Approve minutes
                      </Button>
                    )}
                  </div>
                </div>
                {meeting.minutes.summary && (
                  <p className="text-muted-foreground text-sm">
                    {meeting.minutes.summary}
                  </p>
                )}
                <div className="prose max-w-none whitespace-pre-wrap text-sm">
                  {meeting.minutes.content}
                </div>
                {meeting.minutes.is_approved && (
                  <p className="text-muted-foreground text-xs">
                    Approved by {meeting.minutes.approved_by?.name ?? '—'}
                    {meeting.minutes.approved_at
                      ? ` on ${new Date(meeting.minutes.approved_at).toLocaleString()}`
                      : ''}
                  </p>
                )}
              </div>
            ) : (
              <div className="space-y-4">
                {!meeting.minutes && (
                  <p className="text-muted-foreground text-sm">
                    No minutes yet. Write the official meeting record below.
                  </p>
                )}
                {can.update ? (
                  <form onSubmit={saveMinutes} className="space-y-4 rounded-lg border p-4">
                    <div className="grid gap-4 md:grid-cols-2">
                      <div className="space-y-2">
                        <Label>Format</Label>
                        <Select
                          value={minutesForm.data.format}
                          onValueChange={(value) =>
                            minutesForm.setData('format', value)
                          }
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {minutesFormatOptions.map((option) => (
                              <SelectItem key={option.value} value={option.value}>
                                {option.label}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <Label>Language</Label>
                        <Select
                          value={minutesForm.data.language}
                          onValueChange={(value) =>
                            minutesForm.setData('language', value)
                          }
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            {minutesLanguageOptions.map((option) => (
                              <SelectItem key={option.value} value={option.value}>
                                {option.label}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Summary</Label>
                      <Textarea
                        rows={3}
                        value={minutesForm.data.summary}
                        onChange={(e) =>
                          minutesForm.setData('summary', e.target.value)
                        }
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Content</Label>
                      <Textarea
                        rows={10}
                        value={minutesForm.data.content}
                        onChange={(e) =>
                          minutesForm.setData('content', e.target.value)
                        }
                      />
                      <InputError message={minutesForm.errors.content} />
                    </div>
                    <div className="flex gap-2">
                      <Button type="submit" disabled={minutesForm.processing}>
                        Save minutes
                      </Button>
                      {meeting.minutes && (
                        <Button
                          type="button"
                          variant="outline"
                          onClick={() => setShowMinutesForm(false)}
                        >
                          Cancel
                        </Button>
                      )}
                    </div>
                  </form>
                ) : (
                  <p className="text-muted-foreground text-sm">
                    You do not have permission to write minutes.
                  </p>
                )}
              </div>
            )}
          </div>
        )}

        {tab === 'decisions' && (
          <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div className="flex flex-wrap gap-2 text-sm">
                <span className="rounded-md border px-2 py-1">
                  Pending {decisionStats.pending}
                </span>
                <span className="rounded-md border px-2 py-1">
                  In progress {decisionStats.in_progress}
                </span>
                <span className="rounded-md border px-2 py-1">
                  Completed {decisionStats.completed}
                </span>
                <span className="rounded-md border border-rose-300 px-2 py-1 text-rose-700">
                  Overdue {decisionStats.overdue}
                </span>
              </div>
              {can.update && (
                <Button size="sm" onClick={openDecisionCreate}>
                  <Plus className="me-2 size-4" />
                  Add decision
                </Button>
              )}
            </div>

            {showDecisionForm && can.update && (
              <form
                onSubmit={saveDecision}
                className="space-y-4 rounded-lg border p-4"
              >
                <h4 className="font-medium">
                  {editingDecision ? 'Edit decision' : 'New decision'}
                </h4>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2 md:col-span-2">
                    <Label>Title</Label>
                    <Input
                      value={decisionForm.data.title}
                      onChange={(e) =>
                        decisionForm.setData('title', e.target.value)
                      }
                    />
                    <InputError message={decisionForm.errors.title} />
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <Label>Description</Label>
                    <Textarea
                      rows={4}
                      value={decisionForm.data.description}
                      onChange={(e) =>
                        decisionForm.setData('description', e.target.value)
                      }
                    />
                    <InputError message={decisionForm.errors.description} />
                  </div>
                  <div className="space-y-2">
                    <Label>Type</Label>
                    <Select
                      value={decisionForm.data.decision_type}
                      onValueChange={(value) =>
                        decisionForm.setData('decision_type', value)
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {decisionTypeOptions.map((option) => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Status</Label>
                    <Select
                      value={decisionForm.data.status}
                      onValueChange={(value) =>
                        decisionForm.setData('status', value)
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {decisionStatusOptions.map((option) => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Priority</Label>
                    <Select
                      value={decisionForm.data.priority}
                      onValueChange={(value) =>
                        decisionForm.setData('priority', value)
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {decisionPriorityOptions.map((option) => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Assigned to</Label>
                    <Input
                      value={decisionForm.data.assigned_to}
                      onChange={(e) =>
                        decisionForm.setData('assigned_to', e.target.value)
                      }
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Due date</Label>
                    <Input
                      type="date"
                      value={decisionForm.data.due_date}
                      onChange={(e) =>
                        decisionForm.setData('due_date', e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="flex gap-2">
                  <Button type="submit" disabled={decisionForm.processing}>
                    Save decision
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                      setShowDecisionForm(false)
                      setEditingDecision(null)
                    }}
                  >
                    Cancel
                  </Button>
                </div>
              </form>
            )}

            <div className="space-y-3">
              {(meeting.decisions ?? [])?.data?.map((decision, index) => (
                <MeetingDecisionCard
                  key={decision.id}
                  decision={decision}
                  meetingId={meeting.id}
                  statusOptions={decisionStatusOptions}
                  canEdit={can.update}
                  onEdit={openDecisionEdit}
                  onMoveUp={
                    can.update ? () => moveDecision(index, -1) : undefined
                  }
                  onMoveDown={
                    can.update ? () => moveDecision(index, 1) : undefined
                  }
                />
              ))}
              {(meeting.decisions ?? []).length === 0 && (
                <p className="text-muted-foreground text-sm">
                  No decisions recorded yet.
                </p>
              )}
            </div>
          </div>
        )}

        {tab === 'attachments' && (
          <MeetingAttachmentsPanel
            meetingId={meeting.id}
            attachments={meeting.attachments ?? []}
            canEdit={can.update}
          />
        )}

        {tab === 'print' && (
          <div className="space-y-4 rounded-lg border p-4">
            <p className="text-muted-foreground text-sm">
              Open the formal print report in a new tab, then use your browser
              print dialog.
            </p>
            <Button asChild>
              <a
                href={route('admin.meetings.print', meeting.id)}
                target="_blank"
                rel="noreferrer"
              >
                <Printer className="me-2 size-4" />
                Open print report
              </a>
            </Button>
          </div>
        )}
      </Main>
    </>
  )
}

function InfoBlock({
  label,
  value,
  multiline = false,
}: {
  label: string
  value: string
  multiline?: boolean
}) {
  return (
    <div className="space-y-1">
      <p className="text-muted-foreground text-sm font-medium">{label}</p>
      <p className={cn('text-sm', multiline && 'whitespace-pre-wrap')}>
        {value}
      </p>
    </div>
  )
}

MeetingsShow.layout = {
  breadcrumbs: [
    { title: 'Meetings', href: '/admin/meetings' },
    { title: 'Details', href: '#' },
  ],
}
