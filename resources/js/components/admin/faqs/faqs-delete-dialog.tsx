import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { AlertTriangle } from 'lucide-react'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { getFaqDisplayQuestion } from './data/data'
import { type Faq } from '@/types/models/faq'

type FaqsDeleteDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow: Faq
}

export function FaqsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: FaqsDeleteDialogProps) {
  const displayQuestion = getFaqDisplayQuestion(currentRow)

  const handleDelete = () => {
    router.delete(route('admin.faqs.destroy', currentRow.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      handleConfirm={handleDelete}
      title={
        <span className="text-destructive">
          <AlertTriangle
            className="me-1 inline-block stroke-destructive"
            size={18}
          />{' '}
          Delete FAQ
        </span>
      }
      desc={
        <p>
          Are you sure you want to delete{' '}
          <span className="font-bold">{displayQuestion}</span>?
        </p>
      }
      confirmText="Delete"
      destructive
    />
  )
}
