import { useState } from 'react'
import { createPortal } from 'react-dom'
import { Trash2, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { BeneficiariesMultiDeleteDialog } from './beneficiaries-multi-delete-dialog'

type BeneficiariesBulkActionsProps = {
  selectedIds: number[]
  onClearSelection: () => void
}

export function BeneficiariesBulkActions({
  selectedIds,
  onClearSelection,
}: BeneficiariesBulkActionsProps) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)
  const selectedCount = selectedIds.length

  if (selectedCount === 0 || typeof document === 'undefined') {
    return null
  }

  const label = selectedCount === 1 ? 'beneficiary' : 'beneficiaries'

  return createPortal(
    <>
      <div
        role="toolbar"
        aria-label={`Bulk actions for ${selectedCount} selected ${label}`}
        aria-describedby="beneficiaries-bulk-actions-description"
        className="fixed bottom-6 left-1/2 z-[200] flex -translate-x-1/2 items-center gap-x-2 rounded-xl border bg-background p-2 shadow-xl"
      >
        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="outline"
              size="icon"
              onClick={onClearSelection}
              className="size-6 rounded-full"
              aria-label="Clear selection"
              title="Clear selection"
            >
              <X />
              <span className="sr-only">Clear selection</span>
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Clear selection</p>
          </TooltipContent>
        </Tooltip>

        <Separator className="h-5" orientation="vertical" aria-hidden="true" />

        <div
          className="flex items-center gap-x-1 text-sm"
          id="beneficiaries-bulk-actions-description"
        >
          <Badge
            variant="default"
            className="min-w-8 rounded-lg"
            aria-label={`${selectedCount} selected`}
          >
            {selectedCount}
          </Badge>{' '}
          <span className="hidden sm:inline">{label}</span> selected
        </div>

        <Separator className="h-5" orientation="vertical" aria-hidden="true" />

        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="destructive"
              size="icon"
              onClick={() => setShowDeleteConfirm(true)}
              className="size-8"
              aria-label="Delete selected beneficiaries"
              title="Delete selected beneficiaries"
            >
              <Trash2 />
              <span className="sr-only">Delete selected beneficiaries</span>
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Delete selected beneficiaries</p>
          </TooltipContent>
        </Tooltip>
      </div>

      <BeneficiariesMultiDeleteDialog
        selectedIds={selectedIds}
        open={showDeleteConfirm}
        onOpenChange={setShowDeleteConfirm}
        onDeleted={onClearSelection}
      />
    </>,
    document.body
  )
}
