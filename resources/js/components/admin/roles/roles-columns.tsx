import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { type Role } from '@/types/models/role'
import { DataTableRowActions } from './data-table-row-actions'

export const rolesColumns: ColumnDef<Role>[] = [
  {
    id: 'query',
    accessorKey: 'name',
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Role" />
    ),
    cell: ({ row }) => (
      <div className="flex items-center gap-2">
        <span className="font-medium capitalize">
          {row.getValue<string>('name').replace(/_/g, ' ')}
        </span>
        {row.original.is_system && (
          <Badge variant="secondary" className="text-xs">
            System
          </Badge>
        )}
      </div>
    ),
    enableHiding: false,
  },
  {
    id: 'permissions_count',
    accessorFn: (row) => row.permissions.length,
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Permissions" />
    ),
    cell: ({ row }) => <div>{row.getValue('permissions_count')}</div>,
    enableSorting: false,
  },
  {
    accessorKey: 'users_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Users" />
    ),
    cell: ({ row }) => <div>{row.getValue('users_count') ?? 0}</div>,
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
