import { useEffect, useMemo } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import {
  transactionDirectionOptions,
  transactionTypeOptions,
} from './data/data'
import type {
  AccountOption,
  CurrencyOption,
  PaymentMethodOption,
  Transaction,
} from '@/types/models/transaction'

type TransactionsActionDialogProps = {
  currentRow?: Transaction
  open: boolean
  onOpenChange: (open: boolean) => void
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

function buildInitialFormData(currentRow?: Transaction) {
  return {
    account_id: currentRow ? String(currentRow.account_id) : '',
    transaction_type: currentRow?.transaction_type ?? 'donation',
    direction: currentRow?.direction ?? 'in',
    currency_id: currentRow ? String(currentRow.currency_id) : '',
    gross_amount: currentRow ? String(currentRow.gross_amount) : '',
    fee_amount: currentRow ? String(currentRow.fee_amount) : '0',
    transaction_date: currentRow?.transaction_date ?? todayDateInputValue(),
    reference_number: currentRow?.reference_number ?? '',
    description: currentRow?.description ?? '',
    notes: currentRow?.notes ?? '',
    payment_method_id: currentRow?.payment_method_id
      ? String(currentRow.payment_method_id)
      : '',
  }
}

export function TransactionsActionDialog({
  currentRow,
  open,
  onOpenChange,
  accounts,
  currencies,
  paymentMethods,
}: TransactionsActionDialogProps) {
  const isEdit = !!currentRow
  const form = useForm(buildInitialFormData(currentRow))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(buildInitialFormData(currentRow))
  }, [open, currentRow])

  const netAmount = useMemo(() => {
    const gross = Number(form.data.gross_amount)
    const fee = Number(form.data.fee_amount)

    if (Number.isNaN(gross) || Number.isNaN(fee)) {
      return '—'
    }

    return (gross - fee).toFixed(2)
  }, [form.data.gross_amount, form.data.fee_amount])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      account_id: Number(data.account_id),
      currency_id: Number(data.currency_id),
      gross_amount: Number(data.gross_amount),
      fee_amount: Number(data.fee_amount || 0),
      payment_method_id: data.payment_method_id
        ? Number(data.payment_method_id)
        : null,
    }))

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.put(route('admin.transactions.update', currentRow.id), options)
      return
    }

    form.post(route('admin.transactions.store'), options)
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader className="text-start">
          <DialogTitle>
            {isEdit ? 'Edit Transaction' : 'Add Transaction'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update transaction details and amounts.'
              : 'Record a new financial transaction.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="transaction-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="account_id">Account</Label>
              <Select
                value={form.data.account_id}
                onValueChange={(value) => form.setData('account_id', value)}
              >
                <SelectTrigger id="account_id">
                  <SelectValue placeholder="Select account" />
                </SelectTrigger>
                <SelectContent>
                  {accounts.map((account) => (
                    <SelectItem key={account.id} value={String(account.id)}>
                      {account.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.account_id} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="currency_id">Currency</Label>
              <Select
                value={form.data.currency_id}
                onValueChange={(value) => form.setData('currency_id', value)}
              >
                <SelectTrigger id="currency_id">
                  <SelectValue placeholder="Select currency" />
                </SelectTrigger>
                <SelectContent>
                  {currencies.map((currency) => (
                    <SelectItem key={currency.id} value={String(currency.id)}>
                      {currency.code} ({currency.symbol})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.currency_id} />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="transaction_type">Type</Label>
              <Select
                value={form.data.transaction_type}
                onValueChange={(value) =>
                  form.setData('transaction_type', value)
                }
              >
                <SelectTrigger id="transaction_type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  {transactionTypeOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.transaction_type} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="direction">Direction</Label>
              <Select
                value={form.data.direction}
                onValueChange={(value) => form.setData('direction', value)}
              >
                <SelectTrigger id="direction">
                  <SelectValue placeholder="Select direction" />
                </SelectTrigger>
                <SelectContent>
                  {transactionDirectionOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.direction} />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-3">
            <div className="grid gap-2">
              <Label htmlFor="gross_amount">Gross Amount</Label>
              <Input
                id="gross_amount"
                type="number"
                min={0}
                step="0.01"
                value={form.data.gross_amount}
                onChange={(event) =>
                  form.setData('gross_amount', event.target.value)
                }
                required
              />
              <InputError message={form.errors.gross_amount} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="fee_amount">Fee Amount</Label>
              <Input
                id="fee_amount"
                type="number"
                min={0}
                step="0.01"
                value={form.data.fee_amount}
                onChange={(event) =>
                  form.setData('fee_amount', event.target.value)
                }
              />
              <InputError message={form.errors.fee_amount} />
            </div>

            <div className="grid gap-2">
              <Label>Net Amount</Label>
              <Input value={netAmount} readOnly tabIndex={-1} />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="transaction_date">Transaction Date</Label>
              <Input
                id="transaction_date"
                type="date"
                value={form.data.transaction_date}
                onChange={(event) =>
                  form.setData('transaction_date', event.target.value)
                }
                required
              />
              <InputError message={form.errors.transaction_date} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="payment_method_id">Payment Method</Label>
              <Select
                value={form.data.payment_method_id || 'none'}
                onValueChange={(value) =>
                  form.setData(
                    'payment_method_id',
                    value === 'none' ? '' : value
                  )
                }
              >
                <SelectTrigger id="payment_method_id">
                  <SelectValue placeholder="Optional" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {paymentMethods.map((method) => (
                    <SelectItem key={method.id} value={String(method.id)}>
                      {method.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.payment_method_id} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="reference_number">Reference Number</Label>
            <Input
              id="reference_number"
              value={form.data.reference_number}
              onChange={(event) =>
                form.setData('reference_number', event.target.value)
              }
            />
            <InputError message={form.errors.reference_number} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={form.data.description}
              onChange={(event) =>
                form.setData('description', event.target.value)
              }
              className="min-h-20"
            />
            <InputError message={form.errors.description} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              value={form.data.notes}
              onChange={(event) => form.setData('notes', event.target.value)}
              className="min-h-20"
            />
            <InputError message={form.errors.notes} />
          </div>
        </form>

        <DialogFooter>
          <Button type="submit" form="transaction-form" disabled={form.processing}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
