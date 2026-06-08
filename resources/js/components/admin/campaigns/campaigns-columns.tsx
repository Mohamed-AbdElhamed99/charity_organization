import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { formatAmount, statusTypes } from './data/data'
import { type Campaign } from '@/types/models/campaign'
import { DataTableRowActions } from './data-table-row-actions'

export const campaignsColumns: ColumnDef<Campaign>[] = [
  {
    id: 'query',
    accessorFn: (row) =>
      `${row.title_ar} ${row.title_en} ${row.slug} ${row.category_name ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'title_en',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Title (EN)" />
    ),
    cell: ({ row }) => (
      <div className="font-medium">{row.getValue('title_en')}</div>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'category_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Category" />
    ),
    cell: ({ row }) => (
      <Badge variant="outline">
        {row.getValue<string>('category_name') ?? '—'}
      </Badge>
    ),
    filterFn: (row, id, value) =>
      value.includes(String(row.original.category_id)),
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const status = row.original.status
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
    accessorKey: 'budget',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Budget" />
    ),
    cell: ({ row }) => (
      <div>{formatAmount(row.getValue<number>('budget'))}</div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'donation_target',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Donation Target" />
    ),
    cell: ({ row }) => (
      <div>{formatAmount(row.getValue<number | null>('donation_target'))}</div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'start_date',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Start Date" />
    ),
    cell: ({ row }) => <div>{row.getValue('start_date') ?? '—'}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'expenses_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Expenses" />
    ),
    cell: ({ row }) => <div>{row.getValue('expenses_count') ?? 0}</div>,
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
