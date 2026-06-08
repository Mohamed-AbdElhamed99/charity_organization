import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Table } from '@tanstack/react-table'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { type Account } from '@/types/models/account'

type AccountsMultiDeleteDialogProps = {
  table: Table<Account>
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function AccountsMultiDeleteDialog({
  table,
  open,
  onOpenChange,
}: AccountsMultiDeleteDialogProps) {
  const selectedCount = table.getFilteredSelectedRowModel().rows.length

  const handleDelete = () => {
    const ids = table
      .getFilteredSelectedRowModel()
      .rows.map((row) => row.original.id)

    router.post(
      route('admin.accounts.bulk-destroy'),
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
      title="Delete selected accounts"
      desc={`This will delete ${selectedCount} account${selectedCount === 1 ? '' : 's'}.`}
      confirmText="Delete"
      destructive
    />
  )
}
