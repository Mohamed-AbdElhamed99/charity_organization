import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getAccountDisplayName } from './data/data'
import { type Account } from '@/types/models/account'

type AccountsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Account
}

export function AccountsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: AccountsDeleteDialogProps) {
  const displayName = getAccountDisplayName(currentRow)

  const handleDelete = () => {
    router.delete(route('admin.accounts.destroy', currentRow.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleDelete}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete Account
        </span>
      }
      desc={
        <p>
          Are you sure you want to delete{' '}
          <span className="font-bold">{displayName}</span>?
        </p>
      }
      confirmText="Delete"
      destructive
    />
  )
}
