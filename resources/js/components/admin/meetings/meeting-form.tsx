import { useForm } from '@inertiajs/react'
import { Plus, Trash2 } from 'lucide-react'
import { CampaignMultiSelect } from '@/components/admin/meetings/campaign-multi-select'
import InputError from '@/components/input-error'
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
import type {
  Meeting,
  MeetingAttendee,
  SelectOption,
} from '@/types/models/meeting'

type MeetingFormProps = {
  meeting?: Meeting
  campaignOptions: SelectOption[]
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  locationTypeOptions: SelectOption[]
  attendeeRoleOptions: SelectOption[]
  attendanceStatusOptions: SelectOption[]
  submitUrl: string
  method: 'post' | 'put'
  submitLabel: string
}

type AttendeeFormRow = {
  id?: number
  name: string
  name_en: string
  title: string
  organization: string
  email: string
  phone: string
  attendance_status: string
  role: string
  signature_present: boolean
  notes: string
}

function emptyAttendee(): AttendeeFormRow {
  return {
    name: '',
    name_en: '',
    title: '',
    organization: '',
    email: '',
    phone: '',
    attendance_status: 'invited',
    role: 'member',
    signature_present: false,
    notes: '',
  }
}

function mapAttendee(attendee: MeetingAttendee): AttendeeFormRow {
  return {
    id: attendee.id,
    name: attendee.name,
    name_en: attendee.name_en ?? '',
    title: attendee.title ?? '',
    organization: attendee.organization ?? '',
    email: attendee.email ?? '',
    phone: attendee.phone ?? '',
    attendance_status: attendee.attendance_status,
    role: attendee.role,
    signature_present: attendee.signature_present ?? false,
    notes: attendee.notes ?? '',
  }
}

export function MeetingForm({
  meeting,
  campaignOptions,
  typeOptions,
  statusOptions,
  locationTypeOptions,
  attendeeRoleOptions,
  attendanceStatusOptions,
  submitUrl,
  method,
  submitLabel,
}: MeetingFormProps) {
  const form = useForm({
    title: meeting?.title ?? '',
    title_en: meeting?.title_en ?? '',
    type: meeting?.type ?? 'board',
    status: meeting?.status ?? 'scheduled',
    meeting_date: meeting?.meeting_date ?? '',
    start_time: meeting?.start_time ?? '10:00',
    end_time: meeting?.end_time ?? '',
    location: meeting?.location ?? '',
    location_type: meeting?.location_type ?? 'physical',
    meeting_link: meeting?.meeting_link ?? '',
    agenda: meeting?.agenda ?? '',
    description: meeting?.description ?? '',
    quorum_required: meeting?.quorum_required?.toString() ?? '',
    quorum_met: meeting?.quorum_met ?? false,
    chairperson: meeting?.chairperson ?? '',
    secretary: meeting?.secretary ?? '',
    notes: meeting?.notes ?? '',
    campaign_ids: (meeting?.campaign_ids ?? meeting?.campaigns?.map((c) => c.id) ?? []).map(
      String,
    ),
    attendees: (meeting?.attendees?.data ?? []).map(mapAttendee),
  })

  const locationType = form.data.location_type
  const showLocation = locationType === 'physical' || locationType === 'hybrid'
  const showLink = locationType === 'online' || locationType === 'hybrid'

  const submit = (event: React.FormEvent) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      campaign_ids: data.campaign_ids.map((id) => Number(id)),
      quorum_required:
        data.quorum_required === '' ? null : Number(data.quorum_required),
      end_time: data.end_time || null,
      meeting_link: data.meeting_link || null,
    }))

    const options = { preserveScroll: true }

    if (method === 'put') {
      form.put(submitUrl, options)
      return
    }

    form.post(submitUrl, options)
  }

  const addAttendee = () => {
    form.setData('attendees', [...form.data.attendees, emptyAttendee()])
  }

  const updateAttendee = (
    index: number,
    field: keyof AttendeeFormRow,
    value: string | boolean,
  ) => {
    const attendees = form.data.attendees?.map((attendee, i) =>
      i === index ? { ...attendee, [field]: value } : attendee,
    )
    form.setData('attendees', attendees)
  }

  const removeAttendee = (index: number) => {
    form.setData(
      'attendees',
      form.data.attendees?.filter((_, i) => i !== index),
    )
  }

  return (
    <form onSubmit={submit} className="space-y-8">
      <section className="space-y-4">
        <h3 className="text-lg font-semibold">Basic information</h3>
        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="title">Title</Label>
            <Input
              id="title"
              value={form.data.title}
              onChange={(e) => form.setData('title', e.target.value)}
            />
            <InputError message={form.errors.title} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="title_en">Alternate title</Label>
            <Input
              id="title_en"
              value={form.data.title_en}
              onChange={(e) => form.setData('title_en', e.target.value)}
            />
            <InputError message={form.errors.title_en} />
          </div>
          <div className="space-y-2">
            <Label>Type</Label>
            <Select
              value={form.data.type}
              onValueChange={(value) => form.setData('type', value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                {typeOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.type} />
          </div>
          <div className="space-y-2">
            <Label>Status</Label>
            <Select
              value={form.data.status}
              onValueChange={(value) => form.setData('status', value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.status} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="meeting_date">Meeting date</Label>
            <Input
              id="meeting_date"
              type="date"
              value={form.data.meeting_date}
              onChange={(e) => form.setData('meeting_date', e.target.value)}
            />
            <InputError message={form.errors.meeting_date} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="start_time">Start time</Label>
              <Input
                id="start_time"
                type="time"
                value={form.data.start_time}
                onChange={(e) => form.setData('start_time', e.target.value)}
              />
              <InputError message={form.errors.start_time} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="end_time">End time</Label>
              <Input
                id="end_time"
                type="time"
                value={form.data.end_time}
                onChange={(e) => form.setData('end_time', e.target.value)}
              />
              <InputError message={form.errors.end_time} />
            </div>
          </div>
          <div className="space-y-2">
            <Label>Location type</Label>
            <Select
              value={form.data.location_type}
              onValueChange={(value) => form.setData('location_type', value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select location type" />
              </SelectTrigger>
              <SelectContent>
                {locationTypeOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.location_type} />
          </div>
          {showLocation && (
            <div className="space-y-2">
              <Label htmlFor="location">Location</Label>
              <Input
                id="location"
                value={form.data.location}
                onChange={(e) => form.setData('location', e.target.value)}
              />
              <InputError message={form.errors.location} />
            </div>
          )}
          {showLink && (
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="meeting_link">Meeting link</Label>
              <Input
                id="meeting_link"
                type="url"
                value={form.data.meeting_link}
                onChange={(e) => form.setData('meeting_link', e.target.value)}
              />
              <InputError message={form.errors.meeting_link} />
            </div>
          )}
        </div>
      </section>

      <section className="space-y-4">
        <h3 className="text-lg font-semibold">Details</h3>
        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="chairperson">Chairperson</Label>
            <Input
              id="chairperson"
              value={form.data.chairperson}
              onChange={(e) => form.setData('chairperson', e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="secretary">Secretary</Label>
            <Input
              id="secretary"
              value={form.data.secretary}
              onChange={(e) => form.setData('secretary', e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="quorum_required">Quorum required</Label>
            <Input
              id="quorum_required"
              type="number"
              min={1}
              value={form.data.quorum_required}
              onChange={(e) => form.setData('quorum_required', e.target.value)}
            />
          </div>
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="agenda">Agenda</Label>
            <Textarea
              id="agenda"
              rows={4}
              value={form.data.agenda}
              onChange={(e) => form.setData('agenda', e.target.value)}
            />
          </div>
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              rows={3}
              value={form.data.description}
              onChange={(e) => form.setData('description', e.target.value)}
            />
          </div>
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              rows={3}
              value={form.data.notes}
              onChange={(e) => form.setData('notes', e.target.value)}
            />
          </div>
        </div>
      </section>

      <section className="space-y-4">
        <h3 className="text-lg font-semibold">Linked campaigns</h3>
        <CampaignMultiSelect
          options={campaignOptions}
          value={form.data.campaign_ids}
          onChange={(ids) => form.setData('campaign_ids', ids)}
          error={form.errors.campaign_ids}
        />
      </section>

      <section className="space-y-4">
        <div className="flex items-center justify-between gap-2">
          <h3 className="text-lg font-semibold">Attendees</h3>
          <Button type="button" variant="outline" size="sm" onClick={addAttendee}>
            <Plus className="me-2 size-4" />
            Add attendee
          </Button>
        </div>

        <div className="space-y-4">
          {form.data.attendees?.map((attendee, index) => (
            <div
              key={attendee.id ?? index}
              className="grid gap-3 rounded-lg border p-4 md:grid-cols-6"
            >
              <div className="space-y-2 md:col-span-2">
                <Label>Name</Label>
                <Input
                  value={attendee.name}
                  onChange={(e) =>
                    updateAttendee(index, 'name', e.target.value)
                  } 
                />
                <InputError
                  message={
                    form.errors[`attendees.${index}.name` as keyof typeof form.errors]
                  }
                />
              </div>
              <div className="space-y-2">
                <Label>Title</Label>
                <Input
                  value={attendee.title}
                  onChange={(e) =>
                    updateAttendee(index, 'title', e.target.value)
                  }
                />
              </div>
              <div className="space-y-2">
                <Label>Role</Label>
                <Select
                  value={attendee.role}
                  onValueChange={(value) => updateAttendee(index, 'role', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {attendeeRoleOptions.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input
                  type="email"
                  value={attendee.email}
                  onChange={(e) =>
                    updateAttendee(index, 'email', e.target.value)
                  }
                />
              </div>
              <div className="space-y-2">
                <Label>Attendance</Label>
                <Select
                  value={attendee.attendance_status}
                  onValueChange={(value) =>
                    updateAttendee(index, 'attendance_status', value)
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {attendanceStatusOptions.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="flex items-end md:col-span-6">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => removeAttendee(index)}
                >
                  <Trash2 className="me-2 size-4" />
                  Remove
                </Button>
              </div>
            </div>
          ))}
          {form.data.attendees?.length === 0 && (
            <p className="text-muted-foreground text-sm">
              No attendees added yet.
            </p>
          )}
        </div>
      </section>

      <div className="flex justify-end gap-2">
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Saving…' : submitLabel}
        </Button>
      </div>
    </form>
  )
}
