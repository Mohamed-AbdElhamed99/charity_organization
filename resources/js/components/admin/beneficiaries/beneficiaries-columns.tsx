import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import {
  formatDate,
  statusBadgeColors,
  typeBadgeColors,
} from './data/data'
import type { BeneficiaryListItem } from '@/types/models/beneficiary'
import { DataTableRowActions } from './data-table-row-actions'

export const beneficiariesColumns: ColumnDef<BeneficiaryListItem>[] = [
  {
    id: 'query',
    accessorFn: (row) =>
      `${row.code} ${row.display_name} ${row.primary_contact ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'code',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Code" />
    ),
    cell: ({ row }) => (
      <div className="font-mono text-sm">{row.getValue('code')}</div>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'display_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => (
      <div className="font-medium">{row.getValue('display_name')}</div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'type',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Type" />
    ),
    cell: ({ row }) => {
      const type = row.original.type
      const color = typeBadgeColors.get(type)

      return (
        <Badge variant="outline" className={cn('capitalize', color)}>
          {row.original.type_label}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'status',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const status = row.original.status
      const color = statusBadgeColors.get(status)

      return (
        <Badge variant="outline" className={cn('capitalize', color)}>
          {row.original.status_label}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'primary_contact',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Primary Contact" />
    ),
    cell: ({ row }) => row.getValue('primary_contact') ?? '—',
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Created" />
    ),
    cell: ({ row }) => formatDate(row.getValue('created_at')),
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: ({ row }) => <DataTableRowActions row={row} />,
  },
]
