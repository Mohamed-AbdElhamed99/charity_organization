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
import { ContactMessagesMultiDeleteDialog } from './contact-messages-multi-delete-dialog'
import { type ContactMessage } from '@/types/models/contact-message'

type DataTableBulkActionsProps = {
  table: Table<ContactMessage>
}

export function DataTableBulkActions({ table }: DataTableBulkActionsProps) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  return (
    <>
      <BulkActionsToolbar table={table} entityName="message">
        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="destructive"
              size="icon"
              onClick={() => setShowDeleteConfirm(true)}
              className="size-8"
              aria-label="Delete selected messages"
              title="Delete selected messages"
            >
              <Trash2 />
              <span className="sr-only">Delete selected messages</span>
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Delete selected messages</p>
          </TooltipContent>
        </Tooltip>
      </BulkActionsToolbar>

      <ContactMessagesMultiDeleteDialog
        table={table}
        open={showDeleteConfirm}
        onOpenChange={setShowDeleteConfirm}
      />
    </>
  )
}
