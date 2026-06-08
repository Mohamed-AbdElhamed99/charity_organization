import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { Link, router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Eye, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { type ContactMessage } from '@/types/models/contact-message'

type DataTableRowActionsProps = {
  row: Row<ContactMessage>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const message = row.original

  const handleDelete = () => {
    if (!confirm('Delete this message?')) {
      return
    }

    router.delete(route('admin.contact-messages.destroy', message.id), {
      preserveState: true,
      preserveScroll: true,
    })
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
        <DropdownMenuItem asChild>
          <Link href={route('admin.contact-messages.show', message.id)}>
            View
            <DropdownMenuShortcut>
              <Eye size={16} />
            </DropdownMenuShortcut>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem onClick={handleDelete} className="text-red-500!">
          Delete
          <DropdownMenuShortcut>
            <Trash2 size={16} />
          </DropdownMenuShortcut>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
