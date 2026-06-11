import { Link } from '@inertiajs/react'
import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { show } from '@/routes/admin/donor-profiles'
import type { DonorProfileListItem } from '@/types/models/donor-profile'
import { formatDate, typeBadgeColors } from './data/data'
import { DataTableRowActions } from './data-table-row-actions'

export const donorProfilesColumns: ColumnDef<DonorProfileListItem>[] = [
  {
    id: 'query',
    accessorFn: (row) =>
      `${row.display_name} ${row.user_name ?? ''} ${row.user_email ?? ''} ${row.organization_name ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'display_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => (
      <Link
        href={show.url(row.original.id)}
        className="font-medium hover:underline"
      >
        {row.getValue('display_name')}
      </Link>
    ),
    enableHiding: false,
    enableSorting: false,
  },
  {
    accessorKey: 'user_email',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Email" />
    ),
    cell: ({ row }) => row.getValue('user_email') ?? '—',
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
    accessorKey: 'organization_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Organization" />
    ),
    cell: ({ row }) => row.getValue('organization_name') ?? '—',
    enableSorting: false,
  },
  {
    accessorKey: 'country_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Country" />
    ),
    cell: ({ row }) => row.getValue('country_name') ?? '—',
    enableSorting: false,
  },
  {
    accessorKey: 'state_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="State" />
    ),
    cell: ({ row }) => row.getValue('state_name') ?? '—',
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
    cell: DataTableRowActions,
  },
]
