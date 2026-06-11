import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Eye, Pencil, RotateCcw, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { show } from '@/routes/admin/donor-profiles'
import type { DonorProfileListItem } from '@/types/models/donor-profile'
import { useDonorProfiles } from './donor-profiles-provider'

type DataTableRowActionsProps = {
  row: Row<DonorProfileListItem>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useDonorProfiles()
  const profile = row.original
  const isDeleted = Boolean(profile.deleted_at)

  const handleRestore = () => {
    router.post(
      route('admin.donor-profiles.restore', profile.id),
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
              onClick={() => router.visit(show.url(profile.id))}
            >
              View
              <DropdownMenuShortcut>
                <Eye size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuItem
              onClick={() => router.visit(show.url(profile.id))}
            >
              Edit
              <DropdownMenuShortcut>
                <Pencil size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(profile)
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
