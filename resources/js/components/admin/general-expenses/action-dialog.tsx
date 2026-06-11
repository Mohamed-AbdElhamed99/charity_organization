import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import {
  defaultGeneralExpenseFormValues,
  selectOptionsFromAccounts,
  selectOptionsFromCategories,
  selectOptionsFromPaymentMethods,
} from './data/data'
import type {
  GeneralExpenseAccountOption,
  GeneralExpenseCategoryOption,
  GeneralExpensePaymentMethodOption,
} from '@/types/models/general-expense'

type GeneralExpensesActionDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  categories: GeneralExpenseCategoryOption[]
  accounts: GeneralExpenseAccountOption[]
  paymentMethods: GeneralExpensePaymentMethodOption[]
}

export function GeneralExpensesActionDialog({
  open,
  onOpenChange,
  categories,
  accounts,
  paymentMethods,
}: GeneralExpensesActionDialogProps) {
  const form = useForm(defaultGeneralExpenseFormValues())

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(defaultGeneralExpenseFormValues())
  }, [open])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      account_id: Number(data.account_id),
      amount: Number(data.amount),
      category_id: data.category_id ? Number(data.category_id) : null,
      payment_method_id: data.payment_method_id
        ? Number(data.payment_method_id)
        : null,
    }))

    form.post(route('admin.general-expenses.store'), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  const categoryOptions = selectOptionsFromCategories(categories)
  const accountOptions = selectOptionsFromAccounts(accounts)
  const paymentMethodOptions = selectOptionsFromPaymentMethods(paymentMethods)

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
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader className="text-start">
          <DialogTitle>Record General Expense</DialogTitle>
          <DialogDescription>
            Record a new general expense transaction.
          </DialogDescription>
        </DialogHeader>

        <form
          id="general-expense-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
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
                {accountOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.account_id} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="amount">Amount</Label>
              <Input
                id="amount"
                type="number"
                min="0.01"
                step="0.01"
                value={form.data.amount}
                onChange={(event) =>
                  form.setData('amount', event.target.value)
                }
                required
              />
              <InputError message={form.errors.amount} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="expense_date">Expense date</Label>
              <Input
                id="expense_date"
                type="date"
                value={form.data.expense_date}
                onChange={(event) =>
                  form.setData('expense_date', event.target.value)
                }
                required
              />
              <InputError message={form.errors.expense_date} />
            </div>
          </div>

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="category_id">Category</Label>
              <Select
                value={form.data.category_id}
                onValueChange={(value) => form.setData('category_id', value)}
              >
                <SelectTrigger id="category_id">
                  <SelectValue placeholder="Select category" />
                </SelectTrigger>
                <SelectContent>
                  {categoryOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.category_id} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="payment_method_id">Payment method</Label>
              <Select
                value={form.data.payment_method_id}
                onValueChange={(value) =>
                  form.setData('payment_method_id', value)
                }
              >
                <SelectTrigger id="payment_method_id">
                  <SelectValue placeholder="Select payment method" />
                </SelectTrigger>
                <SelectContent>
                  {paymentMethodOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.payment_method_id} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="vendor_name">Vendor name</Label>
            <Input
              id="vendor_name"
              value={form.data.vendor_name}
              onChange={(event) =>
                form.setData('vendor_name', event.target.value)
              }
            />
            <InputError message={form.errors.vendor_name} />
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="is_recurring"
              checked={form.data.is_recurring}
              onCheckedChange={(checked) =>
                form.setData('is_recurring', checked === true)
              }
            />
            <Label htmlFor="is_recurring">Recurring expense</Label>
            <InputError message={form.errors.is_recurring} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="description">Description</Label>
            <Input
              id="description"
              value={form.data.description}
              onChange={(event) =>
                form.setData('description', event.target.value)
              }
            />
            <InputError message={form.errors.description} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="reference_number">Reference number</Label>
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
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              value={form.data.notes}
              onChange={(event) => form.setData('notes', event.target.value)}
              rows={3}
            />
            <InputError message={form.errors.notes} />
          </div>
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="general-expense-form"
            disabled={form.processing}
          >
            Save expense
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
