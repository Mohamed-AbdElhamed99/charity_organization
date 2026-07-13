import { Head, usePage } from '@inertiajs/react'
import { Printer } from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { Meeting } from '@/types/models/meeting'

type PageProps = {
  report: {
    format: string
    organization: string
    meeting: Meeting
    attended_count: number
    quorum_required: number | null
    quorum_met: boolean
  }
}

export default function MeetingsPrint() {
  const { report } = usePage<PageProps>().props
  const { meeting } = report

  return (
    <>
      <Head title={`Minutes — ${meeting.meeting_number}`} />

      <div className="no-print mx-auto flex max-w-4xl justify-end gap-2 p-4">
        <Button onClick={() => window.print()}>
          <Printer className="me-2 size-4" />
          Print
        </Button>
      </div>

      <article className="meeting-print mx-auto max-w-4xl bg-white p-8 text-black">
        <header className="mb-8 border-b-2 border-black pb-6 text-center">
          <p className="text-sm tracking-[0.2em] uppercase">
            {report.organization}
          </p>
          <p className="mt-1 text-xs text-neutral-600">
            Nonprofit Organization
          </p>
          <h1 className="mt-4 text-2xl font-bold tracking-wide">
            OFFICIAL MEETING MINUTES
          </h1>
        </header>

        <section className="mb-6 grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
          <MetaRow label="Meeting Type" value={meeting.type_label ?? meeting.type} />
          <MetaRow label="Meeting Number" value={meeting.meeting_number} />
          <MetaRow
            label="Date"
            value={meeting.formatted_date ?? meeting.meeting_date}
          />
          <MetaRow
            label="Time"
            value={`${meeting.start_time ?? '—'}${meeting.end_time ? ` — ${meeting.end_time}` : ''}`}
          />
          <MetaRow
            label="Location"
            value={
              meeting.location ??
              meeting.location_type_label ??
              meeting.location_type ??
              '—'
            }
          />
          <MetaRow label="Chairperson" value={meeting.chairperson ?? '—'} />
          <MetaRow label="Secretary" value={meeting.secretary ?? '—'} />
          <MetaRow
            label="Status"
            value={meeting.status_label ?? meeting.status}
          />
        </section>

        <section className="mb-6">
          <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
            Attendees
          </h2>
          <table className="w-full border-collapse text-sm">
            <thead>
              <tr>
                <th className="border border-black px-2 py-1 text-left">#</th>
                <th className="border border-black px-2 py-1 text-left">Name</th>
                <th className="border border-black px-2 py-1 text-left">Title</th>
                <th className="border border-black px-2 py-1 text-left">Role</th>
                <th className="border border-black px-2 py-1 text-left">
                  Attendance
                </th>
              </tr>
            </thead>
            <tbody>
              {(meeting.attendees ?? [])?.data?.map((attendee, index) => (
                <tr key={attendee.id ?? index}>
                  <td className="border border-black px-2 py-1">{index + 1}</td>
                  <td className="border border-black px-2 py-1">
                    {attendee.name}
                  </td>
                  <td className="border border-black px-2 py-1">
                    {attendee.title ?? '—'}
                  </td>
                  <td className="border border-black px-2 py-1">
                    {attendee.role_label ?? attendee.role}
                  </td>
                  <td className="border border-black px-2 py-1">
                    {attendee.attendance_status_label ??
                      attendee.attendance_status}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <p className="mt-2 text-sm">
            Quorum:{' '}
            {report.quorum_required
              ? `${report.attended_count} of ${report.quorum_required} required members present — ${report.quorum_met ? 'QUORUM MET' : 'QUORUM NOT MET'}`
              : `${report.attended_count} attended`}
          </p>
        </section>

        {meeting.agenda && (
          <section className="mb-6">
            <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
              Agenda
            </h2>
            <pre className="font-serif text-sm whitespace-pre-wrap">
              {meeting.agenda}
            </pre>
          </section>
        )}

        <section className="mb-6">
          <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
            Meeting Minutes
          </h2>
          {meeting.minutes ? (
            <div className="space-y-3 text-sm">
              {meeting.minutes.summary && (
                <p>
                  <strong>Summary:</strong> {meeting.minutes.summary}
                </p>
              )}
              <pre className="font-serif whitespace-pre-wrap">
                {meeting.minutes.content}
              </pre>
            </div>
          ) : (
            <p className="text-sm italic">Minutes not recorded.</p>
          )}
        </section>

        <section className="mb-6">
          <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
            Decisions and Resolutions
          </h2>
          {(meeting.decisions ?? []).length === 0 ? (
            <p className="text-sm italic">No decisions recorded.</p>
          ) : (
            <div className="space-y-4">
              {(meeting.decisions ?? [])?.data?.map((decision) => (
                <div key={decision.id} className="text-sm">
                  <p className="font-semibold">
                    Decision No. {decision.decision_number}
                  </p>
                  <p className="font-medium">{decision.title}</p>
                  <p className="mt-1 whitespace-pre-wrap">
                    {decision.description}
                  </p>
                  <p className="mt-1 text-neutral-700">
                    Type: {decision.decision_type_label ?? decision.decision_type}{' '}
                    | Priority: {decision.priority_label ?? decision.priority}
                    {decision.assigned_to
                      ? ` | Assigned To: ${decision.assigned_to}`
                      : ''}
                    {decision.due_date ? ` | Due Date: ${decision.due_date}` : ''}
                  </p>
                </div>
              ))}
            </div>
          )}
        </section>

        {(meeting.campaigns ?? []).length > 0 && (
          <section className="mb-6">
            <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
              Linked Campaigns
            </h2>
            <ul className="list-disc ps-5 text-sm">
              {(meeting.campaigns ?? []).map((campaign) => (
                <li key={campaign.id}>{campaign.title_en}</li>
              ))}
            </ul>
          </section>
        )}

        <section className="mt-10 text-sm">
          <h2 className="mb-3 border-b border-black pb-1 text-sm font-bold tracking-wide uppercase">
            Certification
          </h2>
          <p className="mb-6">
            I hereby certify that these minutes are a true and accurate record of
            the proceedings of the above-named meeting.
          </p>
          <div className="grid gap-8 md:grid-cols-2">
            <SignatureLine label="Secretary" />
            <SignatureLine label="Approved by" />
            <SignatureLine label="Chairperson" />
            <SignatureLine label="Date" />
          </div>
          <p className="mt-8">
            Approval Status:{' '}
            {meeting.minutes?.is_approved
              ? 'Approved'
              : 'Pending Approval'}
          </p>
        </section>
      </article>

      <style>{`
        @media print {
          @page {
            size: A4;
            margin: 2cm;
          }
          body {
            font-family: 'Times New Roman', Times, serif !important;
            background: white !important;
          }
          .no-print {
            display: none !important;
          }
          aside,
          nav,
          header:not(.meeting-print header),
          [data-sidebar],
          [data-slot='sidebar'] {
            display: none !important;
          }
          .meeting-print {
            max-width: none !important;
            padding: 0 !important;
            box-shadow: none !important;
          }
        }
        .meeting-print {
          font-family: 'Times New Roman', Times, serif;
        }
      `}</style>
    </>
  )
}

function MetaRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex gap-2">
      <span className="min-w-32 font-semibold">{label}:</span>
      <span>{value}</span>
    </div>
  )
}

function SignatureLine({ label }: { label: string }) {
  return (
    <div>
      <p className="mb-6 border-b border-black pb-8" />
      <p>
        {label}: ____________________ Date: __________
      </p>
    </div>
  )
}

MeetingsPrint.layout = {
  breadcrumbs: [],
}
