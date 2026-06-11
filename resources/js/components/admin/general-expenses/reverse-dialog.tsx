import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { formatMoney } from '@/components/admin/transactions/data/data'
import { getGeneralExpenseDisplayName } from './data/data'
import type { GeneralExpense } from '@/types/models/general-expense'

type GeneralExpensesReverseDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: GeneralExpense
}

export function GeneralExpensesReverseDialog({
  open,
  onOpenChange,
  currentRow,
}: GeneralExpensesReverseDialogProps) {
  const [processing, setProcessing] = useState(false)
  const displayName = getGeneralExpenseDisplayName(currentRow)

  const handleReverse = () => {
    setProcessing(true)

    router.delete(route('admin.general-expenses.destroy', currentRow.id), {
      preserveScroll: true,
      onFinish: () => setProcessing(false),
      onSuccess: () => onOpenChange(false),
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleReverse}
      isLoading={processing}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Reverse General Expense
        </span>
      }
      desc={
        <div className="space-y-4">
          <p>
            Are you sure you want to reverse{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          <dl className="grid gap-1 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Date</dt>
              <dd>{currentRow.expense_date ?? '—'}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Amount</dt>
              <dd>
                {formatMoney(
                  currentRow.amount,
                  currentRow.transaction?.currency_symbol ?? undefined
                )}
              </dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Category</dt>
              <dd>{currentRow.category_name ?? '—'}</dd>
            </div>
          </dl>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              This will reverse the linked transaction. This action cannot be
              undone.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Reverse"
      destructive
    />
  )
}
