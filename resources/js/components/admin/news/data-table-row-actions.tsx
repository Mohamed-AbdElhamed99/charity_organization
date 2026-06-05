import { DotsHorizontalIcon } from '@radix-ui/react-icons'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type Row } from '@tanstack/react-table'
import { Newspaper, RotateCcw, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { News } from '@/types/models/news'
import { useNews } from './news-provider'

type DataTableRowActionsProps = {
  row: Row<News>
}

export function DataTableRowActions({ row }: DataTableRowActionsProps) {
  const { setOpen, setCurrentRow } = useNews()
  const news = row.original
  const isDeleted = Boolean(news.deleted_at)

  const handleRestore = () => {
    router.post(
      route('admin.news.restore', news.id),
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
                setCurrentRow(news)
                setOpen('edit')
              }}
            >
              Edit
              <DropdownMenuShortcut>
                <Newspaper size={16} />
              </DropdownMenuShortcut>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => {
                setCurrentRow(news)
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
