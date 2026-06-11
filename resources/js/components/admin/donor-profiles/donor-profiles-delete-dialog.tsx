import { useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getDonorDisplayName } from './data/data'
import type { DonorProfileListItem } from '@/types/models/donor-profile'

type DonorProfilesDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: DonorProfileListItem
}

export function DonorProfilesDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: DonorProfilesDeleteDialogProps) {
  const [value, setValue] = useState('')
  const displayName = getDonorDisplayName(currentRow)

  const handleDelete = () => {
    if (value.trim() !== displayName) {
      return
    }

    router.delete(route('admin.donor-profiles.destroy', currentRow.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        setValue('')
        onOpenChange(false)
      },
    })
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
          Delete Donor Profile
        </span>
      }
      desc={
        <div className="space-y-4">
          <p className="mb-2">
            Are you sure you want to delete{' '}
            <span className="font-bold">{displayName}</span>?
          </p>

          <div className="grid gap-2">
            <Label htmlFor="delete-confirm-name">Profile name</Label>
            <Input
              id="delete-confirm-name"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Enter profile name to confirm deletion."
              autoFocus
            />
          </div>

          <Alert variant="destructive">
            <AlertTitle>Warning!</AlertTitle>
            <AlertDescription>
              The donor profile will be soft-deleted. The linked user account
              remains unchanged.
            </AlertDescription>
          </Alert>
        </div>
      }
      confirmText="Delete"
      destructive
    />
  )
}
