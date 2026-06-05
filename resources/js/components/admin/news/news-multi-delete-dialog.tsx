import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Table } from '@tanstack/react-table'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import type { News } from '@/types/models/news'

type NewsMultiDeleteDialogProps<TData> = {
  open: boolean
  onOpenChange: (open: boolean) => void
  table: Table<TData>
}

const CONFIRM_WORD = 'DELETE'

export function NewsMultiDeleteDialog<TData>({
  open,
  onOpenChange,
  table,
}: NewsMultiDeleteDialogProps<TData>) {
  const [value, setValue] = useState('')

  const selectedRows = table.getFilteredSelectedRowModel().rows
  const selectedIds = selectedRows.map((row) => (row.original as News).id)

  const handleDelete = () => {
    if (value.trim() !== CONFIRM_WORD) {
      return
    }

    router.post(
      route('admin.news.bulk-destroy'),
      { ids: selectedIds },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          setValue('')
          table.resetRowSelection()
          onOpenChange(false)
        },
      }
    )
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
      disabled={value.trim() !== CONFIRM_WORD}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete {selectedRows.length}{' '}
          {selectedRows.length > 1 ? 'articles' : 'article'}
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete the selected news articles? <br />
            This action cannot be undone.
          </p>

          <div className="grid gap-2">
            <Label htmlFor="bulk-delete-confirm">
              Confirm by typing &quot;{CONFIRM_WORD}&quot;
            </Label>
            <Input
              id="bulk-delete-confirm"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder={`Type "${CONFIRM_WORD}" to confirm.`}
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              Please be careful, this operation can not be rolled back.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
