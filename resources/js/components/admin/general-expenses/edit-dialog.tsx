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
  defaultEditGeneralExpenseFormValues,
  selectOptionsFromCategories,
} from './data/data'
import type {
  GeneralExpense,
  GeneralExpenseCategoryOption,
} from '@/types/models/general-expense'

type GeneralExpensesEditDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: GeneralExpense
  categories: GeneralExpenseCategoryOption[]
}

export function GeneralExpensesEditDialog({
  open,
  onOpenChange,
  currentRow,
  categories,
}: GeneralExpensesEditDialogProps) {
  const form = useForm(defaultEditGeneralExpenseFormValues(currentRow))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(defaultEditGeneralExpenseFormValues(currentRow))
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      category_id: data.category_id ? Number(data.category_id) : null,
    }))

    form.patch(route('admin.general-expenses.update', currentRow.id), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  const categoryOptions = selectOptionsFromCategories(categories)

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
          <DialogTitle>Edit General Expense</DialogTitle>
          <DialogDescription>
            Update expense metadata. Financial details cannot be changed here.
          </DialogDescription>
        </DialogHeader>

        <form
          id="general-expense-edit-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="edit_name">Name</Label>
            <Input
              id="edit_name"
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="edit_category_id">Category</Label>
            <Select
              value={form.data.category_id}
              onValueChange={(value) => form.setData('category_id', value)}
            >
              <SelectTrigger id="edit_category_id">
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
            <Label htmlFor="edit_vendor_name">Vendor name</Label>
            <Input
              id="edit_vendor_name"
              value={form.data.vendor_name}
              onChange={(event) =>
                form.setData('vendor_name', event.target.value)
              }
            />
            <InputError message={form.errors.vendor_name} />
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="edit_is_recurring"
              checked={form.data.is_recurring}
              onCheckedChange={(checked) =>
                form.setData('is_recurring', checked === true)
              }
            />
            <Label htmlFor="edit_is_recurring">Recurring expense</Label>
            <InputError message={form.errors.is_recurring} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="edit_notes">Notes</Label>
            <Textarea
              id="edit_notes"
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
            form="general-expense-edit-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
