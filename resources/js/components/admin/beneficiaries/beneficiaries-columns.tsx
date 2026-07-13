import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import {
  formatDate,
  statusBadgeColors,
  typeBadgeColors,
} from './data/data'
import type { BeneficiaryListItem } from '@/types/models/beneficiary'
import { DataTableRowActions } from './data-table-row-actions'

function formatAddress(row: BeneficiaryListItem): string {
  const parts = [row.address, row.state_name, row.country_name].filter(Boolean)

  return parts.length > 0 ? parts.join(', ') : '—'
}

export function createBeneficiariesColumns(
  from: number | null
): ColumnDef<BeneficiaryListItem>[] {
  const startIndex = from ?? 1

  return [
    {
      id: 'select',
      header: ({ table }) => (
        <Checkbox
          checked={
            table.getIsAllPageRowsSelected() ||
            (table.getIsSomePageRowsSelected() && 'indeterminate')
          }
          onCheckedChange={(value) =>
            table.toggleAllPageRowsSelected(!!value)
          }
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
        `${row.code} ${row.display_name} ${row.national_id ?? ''} ${row.address ?? ''} ${row.primary_contact ?? ''}`,
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'row_number',
      header: () => <div className="w-8 text-center">#</div>,
      cell: ({ row }) => (
        <div className="w-8 text-center text-muted-foreground tabular-nums">
          {startIndex + row.index}
        </div>
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: 'national_id',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="National ID" />
      ),
      cell: ({ row }) => {
        const nationalId = row.original.national_id

        return (
          <div className="font-mono text-sm">{nationalId ?? '—'}</div>
        )
      },
      enableSorting: false,
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
      id: 'address',
      accessorFn: (row) => formatAddress(row),
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Address" />
      ),
      cell: ({ row }) => (
        <div className="max-w-56 truncate" title={formatAddress(row.original)}>
          {formatAddress(row.original)}
        </div>
      ),
      enableSorting: false,
    },
    {
      id: 'country_id',
      accessorFn: () => '',
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'state_id',
      accessorFn: () => '',
      enableHiding: true,
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
}
