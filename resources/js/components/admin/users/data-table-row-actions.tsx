import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { Link, router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Eye, RotateCcw, Trash2, UserPen } from 'lucide-react'
import { show as usersShow } from '@/routes/admin/users'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { type User } from '@/types/models/user'
import { useUsers } from './users-provider'

type DataTableRowActionsProps = {
  row: Row<User>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useUsers()
  const user = row.original
  const isDeleted = Boolean(user.deleted_at)

  const handleRestore = () => {
    router.post(
      route('admin.users.restore', user.id),
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
        <DropdownMenuItem asChild>
          <Link href={usersShow.url(user.id)}>
            View
            <DropdownMenuShortcut>
              <Eye size={16} />
            </DropdownMenuShortcut>
          </Link>
        </DropdownMenuItem>
        {!isDeleted && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(user)
                setOpen('edit')
              }}
            >
              Edit
              <DropdownMenuShortcut>
                <UserPen size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(user)
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
