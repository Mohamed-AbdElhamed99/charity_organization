import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getTransactionTypeDisplayName } from './data/data'
import { type TransactionType } from '@/types/models/transaction-type'

type TransactionTypesDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: TransactionType
}

export function TransactionTypesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: TransactionTypesDeleteDialogProps) {
  const [value, setValue] = useState('')
  const displayName = getTransactionTypeDisplayName(currentRow)
  const isReferenced = (currentRow.transactions_count ?? 0) > 0
  const isSystem = currentRow.is_system

  const handleDelete = () => {
    if (isSystem || value.trim() !== displayName) {
      return
    }

    router.delete(route('admin.transaction-types.destroy', currentRow.id), {
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
      disabled={isSystem || value.trim() !== displayName}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete Transaction Type
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          {!isSystem && (
            <div className="grid gap-2">
              <Label htmlFor="delete-confirm-name">Transaction type name</Label>
              <Input
                id="delete-confirm-name"
                value={value}
                onChange={(event) => setValue(event.target.value)}
                placeholder="Enter transaction type name to confirm deletion."
                autoFocus
              />
            </div>
          )}

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              {isSystem
                ? 'System transaction types cannot be deleted.'
                : isReferenced
                  ? 'This transaction type is used by transactions and will be deactivated instead of deleted.'
                  : 'This transaction type will be soft-deleted.'}
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
