import { useEffect, useState } from 'react'
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
import { slugify } from '@/lib/utils'
import { type PaymentMethod } from '@/types/models/payment-method'

type PaymentMethodsActionDialogProps = {
  currentRow?: PaymentMethod | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: (method: PaymentMethod) => void
}

export function PaymentMethodsActionDialog({
  currentRow,
  open,
  onOpenChange,
  onSuccess,
}: PaymentMethodsActionDialogProps) {
  const isEdit = !!currentRow
  const [codeTouched, setCodeTouched] = useState(false)

  const form = useForm({
    name: currentRow?.name ?? '',
    code: currentRow?.code ?? '',
    is_active: currentRow?.is_active ?? true,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    setCodeTouched(false)
    form.clearErrors()
    form.setData({
      name: currentRow?.name ?? '',
      code: currentRow?.code ?? '',
      is_active: currentRow?.is_active ?? true,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const removeFlashListener = router.on('flash', (event) => {
      const flash = (event as CustomEvent).detail?.flash as
        | { paymentMethod?: PaymentMethod }
        | undefined
      const method = flash?.paymentMethod

      if (method) {
        onSuccess?.(method)
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
              onChange={(event) => {
                const name = event.target.value
                if (!isEdit && !codeTouched) {
                  form.setData({
                    ...form.data,
                    name,
                    code: slugify(name).slice(0, 50),
                  })
                  return
                }
                form.setData('name', name)
              }}
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="code">Code</Label>
            <Input
              id="code"
              value={form.data.code}
              onChange={(event) => {
                setCodeTouched(true)
                form.setData('code', event.target.value)
              }}
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
