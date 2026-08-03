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
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { slugify } from '@/lib/utils'
import { type TransactionType } from '@/types/models/transaction-type'

type TransactionTypeActionDialogProps = {
  currentRow?: TransactionType | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: (type: TransactionType) => void
}

export function TransactionTypeActionDialog({
  currentRow,
  open,
  onOpenChange,
  onSuccess,
}: TransactionTypeActionDialogProps) {
  const isEdit = !!currentRow
  const isSystem = Boolean(currentRow?.is_system)
  const [codeTouched, setCodeTouched] = useState(false)

  const form = useForm({
    name: currentRow?.name ?? '',
    code: currentRow?.code ?? '',
    description: currentRow?.description ?? '',
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
      description: currentRow?.description ?? '',
      is_active: currentRow?.is_active ?? true,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const removeFlashListener = router.on('flash', (event) => {
      const flash = (event as CustomEvent).detail?.flash as
        | { transactionType?: TransactionType }
        | undefined
      const type = flash?.transactionType

      if (type) {
        onSuccess?.(type)
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
      form.patch(route('admin.transaction-types.update', currentRow.id), options)
      return
    }

    form.post(route('admin.transaction-types.store'), options)
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
            {isEdit ? 'Edit Transaction Type' : 'Add Transaction Type'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the transaction type details.'
              : 'Create a new transaction type.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="transaction-type-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="transaction-type-name">Name</Label>
            <Input
              id="transaction-type-name"
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
            <Label htmlFor="transaction-type-code">Code</Label>
            <Input
              id="transaction-type-code"
              value={form.data.code}
              onChange={(event) => {
                setCodeTouched(true)
                form.setData('code', event.target.value)
              }}
              required
              disabled={isSystem}
            />
            {isSystem ? (
              <p className="text-xs text-muted-foreground">
                System type codes cannot be changed.
              </p>
            ) : null}
            <InputError message={form.errors.code} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="transaction-type-description">Description</Label>
            <Textarea
              id="transaction-type-description"
              value={form.data.description}
              onChange={(event) =>
                form.setData('description', event.target.value)
              }
              rows={3}
            />
            <InputError message={form.errors.description} />
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="transaction-type-is-active"
              checked={form.data.is_active}
              onCheckedChange={(checked) =>
                form.setData('is_active', checked === true)
              }
            />
            <Label htmlFor="transaction-type-is-active">Active</Label>
            <InputError message={form.errors.is_active} />
          </div>
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="transaction-type-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
