import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-react'
import InputError from '@/components/input-error'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { index as contactMessagesIndex } from '@/routes/admin/contact-messages'
import { type ContactMessage } from '@/types/models/contact-message'

type PageProps = {
  message: ContactMessage
}

export default function ContactMessagesShow() {
  const { message } = usePage<PageProps>().props

  const form = useForm({
    review_notes: message.review_notes ?? '',
  })

  const handleMarkReviewed = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    form.patch(route('admin.contact-messages.mark-reviewed', message.id), {
      preserveScroll: true,
    })
  }

  return (
    <>
      <Head title={`Message from ${message.fullname}`} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex items-center gap-3">
          <Button variant="outline" size="sm" asChild>
            <Link href={contactMessagesIndex.url()}>
              <ArrowLeft className="me-1 h-4 w-4" />
              Back
            </Link>
          </Button>
          <Badge variant="outline">
            {message.is_reviewed ? 'Reviewed' : 'Unreviewed'}
          </Badge>
        </div>

        <div className="grid max-w-3xl gap-4 rounded-lg border p-6">
          <div>
            <p className="text-sm text-muted-foreground">From</p>
            <p className="font-medium">{message.fullname}</p>
            <p>{message.email}</p>
            {message.phone && <p>{message.phone}</p>}
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Subject</p>
            <p className="font-medium">{message.subject}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Message</p>
            <p className="whitespace-pre-wrap">{message.message}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Received</p>
            <p>{message.created_at}</p>
          </div>

          {message.is_reviewed && (
            <div>
              <p className="text-sm text-muted-foreground">Reviewed</p>
              <p>
                {message.reviewer_name ?? 'Staff'} — {message.reviewed_at}
              </p>
              {message.review_notes && (
                <p className="mt-2 whitespace-pre-wrap">{message.review_notes}</p>
              )}
            </div>
          )}
        </div>

        {!message.is_reviewed && (
          <form onSubmit={handleMarkReviewed} className="max-w-3xl space-y-4">
            <div className="grid gap-2">
              <Label htmlFor="review_notes">Review notes (optional)</Label>
              <Textarea
                id="review_notes"
                value={form.data.review_notes}
                onChange={(event) =>
                  form.setData('review_notes', event.target.value)
                }
              />
              <InputError message={form.errors.review_notes} />
            </div>
            <Button type="submit" disabled={form.processing}>
              Mark as reviewed
            </Button>
          </form>
        )}
      </Main>
    </>
  )
}

ContactMessagesShow.layout = {
  breadcrumbs: [
    {
      title: 'Contact Messages',
      href: contactMessagesIndex.url(),
    },
    {
      title: 'View Message',
    },
  ],
}
