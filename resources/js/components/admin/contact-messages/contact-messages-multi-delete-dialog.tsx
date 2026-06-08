import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Table } from '@tanstack/react-table'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type ContactMessage } from '@/types/models/contact-message'

type ContactMessagesMultiDeleteDialogProps = {
  table: Table<ContactMessage>
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function ContactMessagesMultiDeleteDialog({
  table,
  open,
  onOpenChange,
}: ContactMessagesMultiDeleteDialogProps) {
  const selectedCount = table.getFilteredSelectedRowModel().rows.length

  const handleDelete = () => {
    const ids = table
      .getFilteredSelectedRowModel()
      .rows.map((row) => row.original.id)

    router.post(
      route('admin.contact-messages.bulk-destroy'),
      { ids },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          table.resetRowSelection()
          onOpenChange(false)
        },
      }
    )
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleDelete}
      title="Delete selected messages"
      desc={`This will delete ${selectedCount} message${selectedCount === 1 ? '' : 's'}.`}
      confirmText="Delete"
      destructive
    />
  )
}
