import { useForm } from '@inertiajs/react'
import { Download, Trash2, Upload } from 'lucide-react'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { MeetingAttachment } from '@/types/models/meeting'
import { route } from 'ziggy-js'

type Props = {
  meetingId: number
  attachments: MeetingAttachment[]
  canEdit: boolean
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) {
    return `${bytes} B`
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function MeetingAttachmentsPanel({
  meetingId,
  attachments,
  canEdit,
}: Props) {
  const form = useForm<{
    file: File | null
    description: string
  }>({
    file: null,
    description: '',
  })

  const submit = (event: React.FormEvent) => {
    event.preventDefault()
    form.post(route('admin.meetings.attachments.store', meetingId), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => form.reset(),
    })
  }

  const destroy = (attachmentId: number) => {
    if (!confirm('Delete this attachment?')) {
      return
    }

    form.delete(
      route('admin.meetings.attachments.destroy', [meetingId, attachmentId]),
      { preserveScroll: true },
    )
  }

  return (
    <div className="space-y-6">
      {canEdit && (
        <form onSubmit={submit} className="space-y-4 rounded-lg border p-4">
          <h4 className="font-medium">Upload attachment</h4>
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="attachment-file">File</Label>
              <Input
                id="attachment-file"
                type="file"
                onChange={(e) =>
                  form.setData('file', e.target.files?.[0] ?? null)
                }
              />
              <InputError message={form.errors.file} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="attachment-description">Description</Label>
              <Input
                id="attachment-description"
                value={form.data.description}
                onChange={(e) => form.setData('description', e.target.value)}
              />
            </div>
          </div>
          <Button type="submit" disabled={form.processing || !form.data.file}>
            <Upload className="me-2 size-4" />
            Upload
          </Button>
        </form>
      )}

      <div className="space-y-3">
        {attachments?.data?.length === 0 ? (
          <p className="text-muted-foreground text-sm">No attachments yet.</p>
        ) : (
          attachments?.data?.map((attachment) => (
            <div
              key={attachment.id}
              className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3"
            >
              <div>
                <p className="font-medium">{attachment.file_name}</p>
                <p className="text-muted-foreground text-sm">
                  {formatBytes(attachment.file_size)}
                  {attachment.description ? ` · ${attachment.description}` : ''}
                  {attachment.uploaded_by
                    ? ` · by ${attachment.uploaded_by.name}`
                    : ''}
                </p>
              </div>
              <div className="flex gap-2">
                <Button variant="outline" size="sm" asChild>
                  <a
                    href={route('admin.meetings.attachments.download', [
                      meetingId,
                      attachment.id,
                    ])}
                  >
                    <Download className="size-4" />
                  </a>
                </Button>
                {canEdit && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => destroy(attachment.id)}
                  >
                    <Trash2 className="size-4 text-rose-600" />
                  </Button>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
