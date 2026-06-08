import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { callTypes } from './data/data'
import { type CampaignCategory } from '@/types/models/campaign-category'
import { DataTableRowActions } from './data-table-row-actions'

export const campaignCategoriesColumns: ColumnDef<CampaignCategory>[] = [
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
    accessorFn: (row) => `${row.name_ar} ${row.name_en}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'name_en',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name (EN)" />
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue('name_en')}</div>,
    enableHiding: false,
  },
  {
    accessorKey: 'name_ar',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name (AR)" />
    ),
    cell: ({ row }) => <div dir="rtl">{row.getValue('name_ar')}</div>,
    enableHiding: false,
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
    accessorKey: 'campaigns_count',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Campaigns" />
    ),
    cell: ({ row }) => <div>{row.getValue('campaigns_count') ?? 0}</div>,
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
