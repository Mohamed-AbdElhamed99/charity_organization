import { useState } from 'react'
import { router } from '@inertiajs/react'
import { AlertTriangle } from 'lucide-react'
import { reverse as reverseTransaction } from '@/routes/admin/transactions'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { formatMoney } from './data/data'
import type { Transaction } from '@/types/models/transaction'

type TransactionsReverseDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  transaction: Transaction | null
}

export function TransactionsReverseDialog({
  open,
  onOpenChange,
  transaction,
}: TransactionsReverseDialogProps) {
  const [processing, setProcessing] = useState(false)

  const handleReverse = () => {
    if (!transaction) {
      return
    }

    setProcessing(true)

    router.post(
      reverseTransaction.url(transaction.id),
      {},
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
        onSuccess: () => onOpenChange(false),
      }
    )
  }

  if (!transaction) {
    return null
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
          Reverse Transaction
        </span>
      }
      desc={
        <div className="space-y-4">
          <p>
            This will create a compensating adjustment entry for transaction{' '}
            <span className="font-bold">#{transaction.id}</span>.
          </p>

          <dl className="grid gap-1 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Type</dt>
              <dd>{transaction.transaction_type_label ?? '—'}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Amount</dt>
              <dd>
                {formatMoney(
                  transaction.net_amount,
                  transaction.currency?.symbol
                )}
              </dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-muted-foreground">Description</dt>
              <dd className="text-end">{transaction.description ?? '—'}</dd>
            </div>
          </dl>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              This action cannot be undone. A new adjustment transaction will be
              recorded.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Reverse"
      destructive
    />
  )
}
