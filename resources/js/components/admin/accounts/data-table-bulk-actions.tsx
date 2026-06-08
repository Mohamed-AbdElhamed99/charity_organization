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
import { AccountsMultiDeleteDialog } from './accounts-multi-delete-dialog'
import { type Account } from '@/types/models/account'

type DataTableBulkActionsProps<TData> = {
  table: Table<TData>
}

export function DataTableBulkActions<TData>({
  table,
}: DataTableBulkActionsProps<TData>) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  return (
    <>
      <BulkActionsToolbar table={table} entityName="Account">
        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="destructive"
              size="icon"
              onClick={() => setShowDeleteConfirm(true)}
              className="size-8"
              aria-label="Delete selected accounts"
              title="Delete selected accounts"
            >
              <Trash2 />
              <span className="sr-only">Delete selected accounts</span>
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Delete selected accounts</p>
          </TooltipContent>
        </Tooltip>
      </BulkActionsToolbar>

      <AccountsMultiDeleteDialog
        table={table as unknown as Table<Account>}
        open={showDeleteConfirm}
        onOpenChange={setShowDeleteConfirm}
      />
    </>
  )
}
