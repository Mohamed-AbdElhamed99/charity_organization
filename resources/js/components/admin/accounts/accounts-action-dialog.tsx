import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
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
import { type Account, type AccountTypeOption, type CurrencyOption } from '@/types/models/account'

type AccountsActionDialogProps = {
  currentRow?: Account
  open: boolean
  onOpenChange: (open: boolean) => void
  currencies: CurrencyOption[]
  accountTypes: AccountTypeOption[]
}

function buildInitialFormData(currentRow?: Account) {
  return {
    name: currentRow?.name ?? '',
    account_number: currentRow?.account_number ?? '',
    bank_name: currentRow?.bank_name ?? '',
    bank_branch: currentRow?.bank_branch ?? '',
    currency_id: currentRow?.currency_id ?? ('' as number | ''),
    type: currentRow?.type ?? ('' as Account['type'] | ''),
    opening_balance: currentRow?.opening_balance ?? 0,
    is_active: currentRow?.is_active ?? true,
    notes: currentRow?.notes ?? '',
  }
}

export function AccountsActionDialog({
  currentRow,
  open,
  onOpenChange,
  currencies,
  accountTypes,
}: AccountsActionDialogProps) {
  const isEdit = !!currentRow
  const form = useForm(buildInitialFormData(currentRow))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(buildInitialFormData(currentRow))
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.accounts.update', currentRow.id), options)
      return
    }

    form.post(route('admin.accounts.store'), options)
  }

  const isBankType = form.data.type === 'bank'

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
          <DialogTitle>{isEdit ? 'Edit Account' : 'Add Account'}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the account details below.'
              : 'Create a new financial account.'}
          </DialogDescription>
        </DialogHeader>

        <form id="account-form" onSubmit={handleSubmit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2 sm:col-span-2">
              <Label htmlFor="name">Account Name</Label>
              <Input
                id="name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                required
              />
              <InputError message={form.errors.name} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="type">Account Type</Label>
              <Select
                value={form.data.type}
                onValueChange={(value) =>
                  form.setData('type', value as Account['type'])
                }
              >
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  {accountTypes.map((t) => (
                    <SelectItem key={t.value} value={t.value}>
                      {t.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="currency_id">Currency</Label>
              <Select
                value={form.data.currency_id ? String(form.data.currency_id) : ''}
                onValueChange={(value) =>
                  form.setData('currency_id', Number(value))
                }
              >
                <SelectTrigger id="currency_id">
                  <SelectValue placeholder="Select currency" />
                </SelectTrigger>
                <SelectContent>
                  {currencies.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.code} – {c.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.currency_id} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="opening_balance">Opening Balance</Label>
              <Input
                id="opening_balance"
                type="number"
                min={0}
                step="0.01"
                value={form.data.opening_balance}
                onChange={(e) =>
                  form.setData('opening_balance', Number(e.target.value))
                }
                required
              />
              <InputError message={form.errors.opening_balance} />
            </div>

            <div className="flex items-end gap-2 pb-2">
              <Checkbox
                id="is_active"
                checked={form.data.is_active}
                onCheckedChange={(checked) =>
                  form.setData('is_active', checked === true)
                }
              />
              <Label htmlFor="is_active">Active</Label>
              <InputError message={form.errors.is_active} />
            </div>
          </div>

          {isBankType && (
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label htmlFor="bank_name">Bank Name</Label>
                <Input
                  id="bank_name"
                  value={form.data.bank_name}
                  onChange={(e) => form.setData('bank_name', e.target.value)}
                />
                <InputError message={form.errors.bank_name} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="bank_branch">Branch</Label>
                <Input
                  id="bank_branch"
                  value={form.data.bank_branch}
                  onChange={(e) => form.setData('bank_branch', e.target.value)}
                />
                <InputError message={form.errors.bank_branch} />
              </div>

              <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor="account_number">Account Number</Label>
                <Input
                  id="account_number"
                  value={form.data.account_number}
                  onChange={(e) =>
                    form.setData('account_number', e.target.value)
                  }
                  className="font-mono"
                />
                <InputError message={form.errors.account_number} />
              </div>
            </div>
          )}

          <div className="grid gap-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              value={form.data.notes}
              onChange={(e) => form.setData('notes', e.target.value)}
              rows={3}
            />
            <InputError message={form.errors.notes} />
          </div>
        </form>

        <DialogFooter>
          <Button type="submit" form="account-form" disabled={form.processing}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
