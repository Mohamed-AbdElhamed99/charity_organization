import { router } from '@inertiajs/react'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { destroy } from '@/routes/admin/beneficiaries'
import { useBeneficiaries } from './beneficiaries-provider'

export function BeneficiariesDeleteDialog() {
  const { open, setOpen, currentRow, setCurrentRow } = useBeneficiaries()

  const handleDelete = () => {
    if (!currentRow) {
      return
    }

    router.delete(destroy.url(currentRow.id), {
      preserveScroll: true,
      onSuccess: () => {
        setOpen(null)
        setCurrentRow(null)
      },
    })
  }

  return (
    <ConfirmDialog
      open={open === 'delete'}
      onOpenChange={(isOpen) => {
        if (!isOpen) {
          setOpen(null)
          setCurrentRow(null)
        }
      }}
      title="Delete beneficiary"
      desc={`This will permanently delete ${currentRow?.code ?? 'this beneficiary'}. This action cannot be undone.`}
      confirmText="Delete"
      destructive
      handleConfirm={handleDelete}
    />
  )
}
