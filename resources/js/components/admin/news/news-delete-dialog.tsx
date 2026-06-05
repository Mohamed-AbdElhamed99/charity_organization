import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import type { News } from '@/types/models/news'

type NewsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: News
}

export function NewsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: NewsDeleteDialogProps) {
  const [value, setValue] = useState('')

  const handleDelete = () => {
    if (value.trim() !== currentRow.title_en) {
      return
    }

    router.delete(route('admin.news.destroy', currentRow.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        setValue('')
        onOpenChange(false)
      },
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          setValue('')
        }
        onOpenChange(state)
      }}
      handleConfirm={handleDelete}
      disabled={value.trim() !== currentRow.title_en}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete News
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{currentRow.title_en}</span>?
            <br />
            This action will soft-delete the article. You can restore it later.
          </p>

          <div className="grid gap-2">
            <Label htmlFor="delete-confirm-title">Title (English)</Label>
            <Input
              id="delete-confirm-title"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Enter English title to confirm deletion."
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              Please be careful, this operation can not be rolled back easily.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
