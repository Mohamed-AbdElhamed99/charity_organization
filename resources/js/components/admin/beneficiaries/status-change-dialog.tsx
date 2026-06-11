import { useForm } from '@inertiajs/react'
import { ConfirmDialog } from '@/components/confirm-dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { status } from '@/routes/admin/beneficiaries'
import type { BeneficiaryStatus } from '@/types/models/beneficiary'
import { statusOptions } from './data/data'

type StatusChangeDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  beneficiaryId: number
  currentStatus: BeneficiaryStatus
}

export function StatusChangeDialog({
  open,
  onOpenChange,
  beneficiaryId,
  currentStatus,
}: StatusChangeDialogProps) {
  const form = useForm({
    status: currentStatus,
  })

  const handleConfirm = () => {
    form.patch(status.url(beneficiaryId), {
      preserveScroll: true,
      only: ['beneficiary'],
      onSuccess: () => onOpenChange(false),
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      title="Change beneficiary status"
      desc={
        <div className="grid gap-2 py-2">
          <Select
            value={form.data.status}
            onValueChange={(value) =>
              form.setData('status', value as BeneficiaryStatus)
            }
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {statusOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      }
      confirmText="Update status"
      handleConfirm={handleConfirm}
    />
  )
}
