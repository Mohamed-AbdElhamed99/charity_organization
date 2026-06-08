import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { statusBadgeColors, typeBadgeColors } from './data/data'
import { type Account } from '@/types/models/account'
import { DataTableRowActions } from './data-table-row-actions'

export const accountsColumns: ColumnDef<Account>[] = [
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
    accessorFn: (row) =>
      `${row.name} ${row.account_number ?? ''} ${row.bank_name ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => (
      <div className="max-w-xs truncate font-medium">{row.getValue('name')}</div>
    ),
    enableHiding: false,
  },
  {
    id: 'type',
    accessorKey: 'type',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Type" />
    ),
    cell: ({ row }) => {
      const account = row.original
      const badgeColor = typeBadgeColors.get(account.type)

      return (
        <Badge variant="outline" className={cn('capitalize', badgeColor)}>
          {account.type_label}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'bank_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Bank" />
    ),
    cell: ({ row }) => (
      <div>{row.getValue('bank_name') || '—'}</div>
    ),
  },
  {
    accessorKey: 'account_number',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Account Number" />
    ),
    cell: ({ row }) => (
      <div className="font-mono text-sm">
        {row.getValue('account_number') || '—'}
      </div>
    ),
  },
  {
    accessorKey: 'opening_balance',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Opening Balance" />
    ),
    cell: ({ row }) => {
      const account = row.original
      const symbol = account.currency?.symbol ?? ''

      return (
        <div className="text-end font-mono">
          {symbol}
          {Number(row.getValue('opening_balance')).toLocaleString(undefined, {
            minimumFractionDigits: 2,
          })}
        </div>
      )
    },
  },
  {
    id: 'status',
    accessorFn: (row) => (row.is_active ? 'active' : 'inactive'),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const isActive = row.original.is_active
      const badgeColor = statusBadgeColors.get(isActive)

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
