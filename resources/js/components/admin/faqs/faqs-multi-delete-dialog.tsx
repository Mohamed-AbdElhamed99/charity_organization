import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Table } from '@tanstack/react-table'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Faq } from '@/types/models/faq'

type FaqsMultiDeleteDialogProps = {
  table: Table<Faq>
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function FaqsMultiDeleteDialog({
  table,
  open,
  onOpenChange,
}: FaqsMultiDeleteDialogProps) {
  const selectedCount = table.getFilteredSelectedRowModel().rows.length

  const handleDelete = () => {
    const ids = table
      .getFilteredSelectedRowModel()
      .rows.map((row) => row.original.id)

    router.post(
      route('admin.faqs.bulk-destroy'),
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
      title="Delete selected FAQs"
      desc={`This will delete ${selectedCount} FAQ${selectedCount === 1 ? '' : 's'}.`}
      confirmText="Delete"
      destructive
    />
  )
}
