import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getCurrencyDisplayName } from './data/data'
import { type Currency } from '@/types/models/currency'

type CurrenciesDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Currency
}

export function CurrenciesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: CurrenciesDeleteDialogProps) {
  const [value, setValue] = useState('')
  const displayName = getCurrencyDisplayName(currentRow)
  const isReferenced =
    (currentRow.accounts_count ?? 0) > 0 ||
    (currentRow.transactions_count ?? 0) > 0

  const handleDelete = () => {
    if (value.trim() !== displayName) {
      return
    }

    router.delete(route('admin.currencies.destroy', currentRow.id), {
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
      disabled={value.trim() !== displayName || isReferenced}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete Currency
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          <div className="grid gap-2">
            <Label htmlFor="delete-confirm-code">Currency code</Label>
            <Input
              id="delete-confirm-code"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Enter currency code to confirm deletion."
              autoFocus
              disabled={isReferenced}
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              {isReferenced
                ? 'This currency is used by accounts or transactions and cannot be deleted. Deactivate it instead.'
                : 'This currency will be permanently deleted.'}
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
