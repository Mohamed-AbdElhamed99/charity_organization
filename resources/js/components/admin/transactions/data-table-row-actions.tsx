import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { Link } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Eye, Pencil, RotateCcw } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { Transaction } from '@/types/models/transaction'
import { useTransactions } from './transactions-provider'

type DataTableRowActionsProps = {
  row: Row<Transaction>
  onReverse: (transaction: Transaction) => void
}

export function DataTableRowActions({
  row,
  onReverse,
}: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useTransactions()
  const transaction = row.original
  const canReverse =
    transaction.transaction_type !== 'adjustment' && !transaction.deleted_at

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className="flex h-8 w-8 p-0 data-[state=open]:bg-muted"
        >
          <DotsHorizontalIcon className="h-4 w-4" />
          <span className="sr-only">Open menu</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-44">
        <DropdownMenuItem asChild>
          <Link href={route('admin.transactions.show', transaction.id)}>
            View
            <DropdownMenuShortcut>
              <Eye size={16} />
            </DropdownMenuShortcut>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem
          onClick={() => {
            setCurrentRow(transaction)
            setOpen('edit')
          }}
        >
          Edit
          <DropdownMenuShortcut>
            <Pencil size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
        {canReverse && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onReverse(transaction)}>
              Reverse
              <DropdownMenuShortcut>
                <RotateCcw size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
