import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getPaymentMethodDisplayName } from './data/data'
import { type PaymentMethod } from '@/types/models/payment-method'

type PaymentMethodsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: PaymentMethod
}

export function PaymentMethodsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: PaymentMethodsDeleteDialogProps) {
  const [value, setValue] = useState('')
  const displayName = getPaymentMethodDisplayName(currentRow)
  const isReferenced = (currentRow.transactions_count ?? 0) > 0

  const handleDelete = () => {
    if (value.trim() !== displayName) {
      return
    }

    router.delete(route('admin.payment-methods.destroy', currentRow.id), {
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
      disabled={value.trim() !== displayName}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete Payment Method
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          <div className="grid gap-2">
            <Label htmlFor="delete-confirm-name">Payment method name</Label>
            <Input
              id="delete-confirm-name"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Enter payment method name to confirm deletion."
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              {isReferenced
                ? 'This payment method is used by transactions and will be deactivated instead of deleted.'
                : 'This payment method will be soft-deleted.'}
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
