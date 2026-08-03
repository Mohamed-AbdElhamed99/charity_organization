import { useEffect } from 'react'
import { router, useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
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
import InputError from '@/components/input-error'
import { type Currency } from '@/types/models/currency'

type CurrenciesActionDialogProps = {
  currentRow?: Currency | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: (currency: Currency) => void
}

export function CurrenciesActionDialog({
  currentRow,
  open,
  onOpenChange,
  onSuccess,
}: CurrenciesActionDialogProps) {
  const isEdit = !!currentRow

  const form = useForm({
    code: currentRow?.code ?? '',
    name: currentRow?.name ?? '',
    symbol: currentRow?.symbol ?? '',
    is_active: currentRow?.is_active ?? true,
    is_default: currentRow?.is_default ?? false,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      code: currentRow?.code ?? '',
      name: currentRow?.name ?? '',
      symbol: currentRow?.symbol ?? '',
      is_active: currentRow?.is_active ?? true,
      is_default: currentRow?.is_default ?? false,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const removeFlashListener = router.on('flash', (event) => {
      const flash = (event as CustomEvent).detail?.flash as
        | { currency?: Currency }
        | undefined
      const currency = flash?.currency

      if (currency) {
        onSuccess?.(currency)
      }

      removeFlashListener()
    })

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
      onError: () => removeFlashListener(),
      onCancel: () => removeFlashListener(),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.currencies.update', currentRow.id), options)
      return
    }

    form.post(route('admin.currencies.store'), options)
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
      <DialogContent className="sm:max-w-lg">
        <DialogHeader className="text-start">
          <DialogTitle>
            {isEdit ? 'Edit Currency' : 'Add Currency'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the currency details.'
              : 'Create a new currency for ledger and FX equivalents.'}
          </DialogDescription>
        </DialogHeader>

        <form id="currency-form" onSubmit={handleSubmit} className="space-y-4">
          <div className="grid gap-2">
            <Label htmlFor="code">Code</Label>
            <Input
              id="code"
              value={form.data.code}
              onChange={(event) =>
                form.setData('code', event.target.value.toUpperCase())
              }
              placeholder="USD"
              maxLength={10}
              required
            />
            <InputError message={form.errors.code} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              placeholder="US Dollar"
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="symbol">Symbol</Label>
            <Input
              id="symbol"
              value={form.data.symbol}
              onChange={(event) => form.setData('symbol', event.target.value)}
              placeholder="$"
              maxLength={10}
            />
            <InputError message={form.errors.symbol} />
          </div>

          <div className="flex items-center gap-2">
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

          <div className="flex items-center gap-2">
            <Checkbox
              id="is_default"
              checked={form.data.is_default}
              onCheckedChange={(checked) =>
                form.setData('is_default', checked === true)
              }
            />
            <Label htmlFor="is_default">Default currency</Label>
            <InputError message={form.errors.is_default} />
          </div>
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="currency-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
