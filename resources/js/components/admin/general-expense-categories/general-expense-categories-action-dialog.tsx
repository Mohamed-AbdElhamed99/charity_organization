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
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'

type GeneralExpenseCategoriesActionDialogProps = {
  currentRow?: GeneralExpenseCategory
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function GeneralExpenseCategoriesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: GeneralExpenseCategoriesActionDialogProps) {
  const isEdit = !!currentRow

  const form = useForm({
    name: currentRow?.name ?? '',
    description: currentRow?.description ?? '',
    is_active: currentRow?.is_active ?? true,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      name: currentRow?.name ?? '',
      description: currentRow?.description ?? '',
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
      form.patch(
        route('admin.general-expense-categories.update', currentRow.id),
        options
      )
      return
    }

    form.post(route('admin.general-expense-categories.store'), options)
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
            {isEdit ? 'Edit General Expense Category' : 'Add General Expense Category'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the general expense category details.'
              : 'Create a new general expense category.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="general-expense-category-form"
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
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
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
            form="general-expense-category-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
