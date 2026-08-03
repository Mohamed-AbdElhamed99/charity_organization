import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { callTypes } from './data/data'
import { type Currency } from '@/types/models/currency'
import { DataTableRowActions } from './data-table-row-actions'

export const currenciesColumns: ColumnDef<Currency>[] = [
  {
    id: 'query',
    accessorFn: (row) => `${row.code} ${row.name} ${row.symbol ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'code',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Code" />
    ),
    cell: ({ row }) => (
      <code className="rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
        {row.getValue('code')}
      </code>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue('name')}</div>,
    enableHiding: false,
  },
  {
    accessorKey: 'symbol',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Symbol" />
    ),
    cell: ({ row }) => <div>{row.getValue('symbol') || '—'}</div>,
  },
  {
    id: 'status',
    accessorFn: (row) => (row.is_active ? 'active' : 'inactive'),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const isActive = row.original.is_active
      const badgeColor = callTypes.get(isActive)

      return (
        <Badge variant="outline" className={cn('capitalize', badgeColor)}>
          {isActive ? 'Active' : 'Inactive'}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'is_default',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Default" />
    ),
    cell: ({ row }) =>
      row.original.is_default ? (
        <Badge variant="secondary">Default</Badge>
      ) : (
        <span className="text-muted-foreground">—</span>
      ),
    enableSorting: false,
  },
  {
    accessorKey: 'accounts_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Accounts" />
    ),
    cell: ({ row }) => <div>{row.getValue('accounts_count') ?? 0}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'transactions_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Transactions" />
    ),
    cell: ({ row }) => <div>{row.getValue('transactions_count') ?? 0}</div>,
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
