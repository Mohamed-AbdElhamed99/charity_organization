import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Pencil, RotateCcw, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { type PaymentMethod } from '@/types/models/payment-method'
import { usePaymentMethods } from './payment-methods-provider'

type DataTableRowActionsProps = {
  row: Row<PaymentMethod>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = usePaymentMethods()
  const method = row.original
  const isDeleted = Boolean(method.deleted_at)

  const handleRestore = () => {
    router.post(
      route('admin.payment-methods.restore', method.id),
      {},
      { preserveState: true, preserveScroll: true }
    )
  }

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
      <DropdownMenuContent align="end" className="w-40">
        {!isDeleted && (
          <>
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(method)
                setOpen('edit')
              }}
            >
              Edit
              <DropdownMenuShortcut>
                <Pencil size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(method)
                setOpen('delete')
              }}
              className="text-red-500!"
            >
              Delete
              <DropdownMenuShortcut>
                <Trash2 size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
          </>
        )}
        {isDeleted && (
          <DropdownMenuItem onClick={handleRestore}>
            Restore
            <DropdownMenuShortcut>
              <RotateCcw size={16} />
            </DropdownMenuShortcut>
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
