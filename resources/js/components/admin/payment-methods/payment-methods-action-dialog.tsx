import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
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
import { type PaymentMethod } from '@/types/models/payment-method'

type PaymentMethodsActionDialogProps = {
  currentRow?: PaymentMethod
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function PaymentMethodsActionDialog({
  currentRow,
  open,
  onOpenChange,
}: PaymentMethodsActionDialogProps) {
  const isEdit = !!currentRow

  const form = useForm({
    name: currentRow?.name ?? '',
    code: currentRow?.code ?? '',
    is_active: currentRow?.is_active ?? true,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      name: currentRow?.name ?? '',
      code: currentRow?.code ?? '',
      is_active: currentRow?.is_active ?? true,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.payment-methods.update', currentRow.id), options)
      return
    }

    form.post(route('admin.payment-methods.store'), options)
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
            {isEdit ? 'Edit Payment Method' : 'Add Payment Method'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the payment method details.'
              : 'Create a new payment method.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="payment-method-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
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

          <div className="grid gap-2">
            <Label htmlFor="code">Code</Label>
            <Input
              id="code"
              value={form.data.code}
              onChange={(event) => form.setData('code', event.target.value)}
              required
            />
            <InputError message={form.errors.code} />
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
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="payment-method-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
