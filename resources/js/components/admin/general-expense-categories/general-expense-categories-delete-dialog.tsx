import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getGeneralExpenseCategoryDisplayName } from './data/data'
import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'

type GeneralExpenseCategoriesDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: GeneralExpenseCategory
}

export function GeneralExpenseCategoriesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: GeneralExpenseCategoriesDeleteDialogProps) {
  const [value, setValue] = useState('')
  const displayName = getGeneralExpenseCategoryDisplayName(currentRow)
  const isReferenced = (currentRow.expenses_count ?? 0) > 0

  const handleDelete = () => {
    if (value.trim() !== displayName) {
      return
    }

    router.delete(
      route('admin.general-expense-categories.destroy', currentRow.id),
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          setValue('')
          onOpenChange(false)
        },
      }
    )
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          setValue('')
        }
        onOpenChange(state)
      }}
      handleConfirm={handleDelete}
      disabled={value.trim() !== displayName}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete General Expense Category
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          <div className="grid gap-2">
            <Label htmlFor="delete-confirm-name">Category name</Label>
            <Input
              id="delete-confirm-name"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Enter category name to confirm deletion."
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              {isReferenced
                ? 'This category is in use and will be deactivated instead of deleted.'
                : 'This general expense category will be soft-deleted.'}
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
