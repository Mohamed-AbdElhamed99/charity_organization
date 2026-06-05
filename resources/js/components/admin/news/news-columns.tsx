import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import type { News } from '@/types/models/news'
import { getNewsDisplayStatus, statusTypes } from './data/data'
import { DataTableRowActions } from './data-table-row-actions'

export const newsColumns: ColumnDef<News>[] = [
  {
    id: 'select',
    header: ({ table }) => (
      <Checkbox
        checked={
          table.getIsAllPageRowsSelected() ||
          (table.getIsSomePageRowsSelected() && 'indeterminate')
        }
        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
        aria-label="Select all"
        className="translate-y-0.5"
      />
    ),
    meta: {
      className: cn('inset-s-0 z-10 rounded-tl-[inherit] max-md:sticky'),
    },
    cell: ({ row }) => (
      <Checkbox
        checked={row.getIsSelected()}
        onCheckedChange={(value) => row.toggleSelected(!!value)}
        aria-label="Select row"
        className="translate-y-0.5"
      />
    ),
    enableSorting: false,
    enableHiding: false,
  },
  {
    id: 'query',
    accessorFn: (row) => `${row.title_ar} ${row.title_en} ${row.slug}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    id: 'article',
    accessorKey: 'title_en',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Article" />
    ),
    cell: ({ row }) => {
      const news = row.original

      return (
        <div className="flex items-center gap-3 ps-1">
          {news.thumbnail ? (
            <img
              src={news.thumbnail}
              alt={news.title_en}
              className="size-10 rounded-md object-cover"
            />
          ) : (
            <div className="flex size-10 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground">
              N/A
            </div>
          )}
          <div className="min-w-0">
            <p className="truncate font-medium">{news.title_en}</p>
            <p className="truncate text-sm text-muted-foreground">{news.slug}</p>
          </div>
        </div>
      )
    },
    enableHiding: false,
  },
  {
    accessorKey: 'category_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Category" />
    ),
    cell: ({ row }) => (
      <Badge variant="outline">{row.getValue<string>('category_name') ?? '—'}</Badge>
    ),
    filterFn: (row, id, value) => value.includes(String(row.original.category_id)),
    enableSorting: false,
  },
  {
    id: 'status',
    accessorFn: (row) => getNewsDisplayStatus(row),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const status = getNewsDisplayStatus(row.original)
      const badgeColor = statusTypes.get(status)

      return (
        <Badge variant="outline" className={cn('capitalize', badgeColor)}>
          {status}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'is_private',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Visibility" />
    ),
    cell: ({ row }) => (
      <Badge variant="outline">
        {row.original.is_private ? 'Members only' : 'Public'}
      </Badge>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'published_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Published" />
    ),
    cell: ({ row }) => <div>{row.getValue('published_at') ?? '—'}</div>,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Created" />
    ),
    cell: ({ row }) => <div>{row.getValue('created_at')}</div>,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
