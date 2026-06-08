import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { callTypes } from './data/data'
import { type Faq } from '@/types/models/faq'
import { DataTableRowActions } from './data-table-row-actions'

export const faqsColumns: ColumnDef<Faq>[] = [
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
    accessorFn: (row) => `${row.question_ar} ${row.question_en ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'question_en',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Question (EN)" />
    ),
    cell: ({ row }) => (
      <div className="max-w-xs truncate font-medium">
        {row.getValue('question_en') || '—'}
      </div>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'question_ar',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Question (AR)" />
    ),
    cell: ({ row }) => (
      <div dir="rtl" className="max-w-xs truncate">
        {row.getValue('question_ar')}
      </div>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'sort_order',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Order" />
    ),
    cell: ({ row }) => <div>{row.getValue('sort_order')}</div>,
  },
  {
    id: 'status',
    accessorFn: (row) => (row.is_published ? 'published' : 'draft'),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const isPublished = row.original.is_published
      const badgeColor = callTypes.get(isPublished)

      return (
        <Badge variant="outline" className={cn('capitalize', badgeColor)}>
          {isPublished ? 'Published' : 'Draft'}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Created At" />
    ),
    cell: ({ row }) => <div>{row.getValue('created_at')}</div>,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
