import { useState } from 'react'
import { type Table } from '@tanstack/react-table'
import { Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { DataTableBulkActions as BulkActionsToolbar } from '@/components/data-table'
import { FaqsMultiDeleteDialog } from './faqs-multi-delete-dialog'

type DataTableBulkActionsProps<TData> = {
  table: Table<TData>
}

export function DataTableBulkActions<TData>({
  table,
}: DataTableBulkActionsProps<TData>) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  return (
    <>
      <BulkActionsToolbar table={table} entityName="FAQ">
        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="destructive"
              size="icon"
              onClick={() => setShowDeleteConfirm(true)}
              className="size-8"
              aria-label="Delete selected FAQs"
              title="Delete selected FAQs"
            >
              <Trash2 />
              <span className="sr-only">Delete selected FAQs</span>
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Delete selected FAQs</p>
          </TooltipContent>
        </Tooltip>
      </BulkActionsToolbar>

      <FaqsMultiDeleteDialog
        table={table as unknown as Table<import('@/types/models/faq').Faq>}
        open={showDeleteConfirm}
        onOpenChange={setShowDeleteConfirm}
      />
    </>
  )
}
