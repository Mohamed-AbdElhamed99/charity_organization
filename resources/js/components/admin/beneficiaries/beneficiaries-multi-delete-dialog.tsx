import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'

type BeneficiariesMultiDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  selectedIds: number[]
  onDeleted: () => void
}

const CONFIRM_WORD = 'DELETE'

export function BeneficiariesMultiDeleteDialog({
  open,
  onOpenChange,
  selectedIds,
  onDeleted,
}: BeneficiariesMultiDeleteDialogProps) {
  const [value, setValue] = useState('')
  const selectedCount = selectedIds.length

  const handleDelete = () => {
    if (value.trim() !== CONFIRM_WORD || selectedCount === 0) {
      return
    }

    router.post(
      route('admin.beneficiaries.bulk-destroy'),
      { ids: selectedIds },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          setValue('')
          onDeleted()
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
      disabled={value.trim() !== CONFIRM_WORD}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete {selectedCount}{' '}
          {selectedCount > 1 ? 'beneficiaries' : 'beneficiary'}
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete the selected beneficiaries? <br />
            This action cannot be undone.
          </p>

          <div className="grid gap-2">
            <Label htmlFor="bulk-delete-confirm">
              Confirm by typing &quot;{CONFIRM_WORD}&quot;
            </Label>
            <Input
              id="bulk-delete-confirm"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder={`Type "${CONFIRM_WORD}" to confirm.`}
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              Please be careful, this operation can not be rolled back.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
