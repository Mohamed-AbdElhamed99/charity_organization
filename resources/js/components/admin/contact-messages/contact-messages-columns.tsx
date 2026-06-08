import { Link } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { DataTableColumnHeader } from '@/components/data-table'
import { callTypes } from './data/data'
import { type ContactMessage } from '@/types/models/contact-message'
import { DataTableRowActions } from './data-table-row-actions'

export const contactMessagesColumns: ColumnDef<ContactMessage>[] = [
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
      `${row.fullname} ${row.email} ${row.subject} ${row.message}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'fullname',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => (
      <Link
        href={route('admin.contact-messages.show', row.original.id)}
        className="font-medium hover:underline"
      >
        {row.getValue('fullname')}
      </Link>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'email',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Email" />
    ),
    cell: ({ row }) => <div>{row.getValue('email')}</div>,
  },
  {
    accessorKey: 'subject',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Subject" />
    ),
    cell: ({ row }) => (
      <div className="max-w-xs truncate">{row.getValue('subject')}</div>
    ),
  },
  {
    id: 'status',
    accessorFn: (row) => (row.is_reviewed ? 'reviewed' : 'unreviewed'),
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Status" />
    ),
    cell: ({ row }) => {
      const isReviewed = row.original.is_reviewed
      const badgeColor = callTypes.get(isReviewed)

      return (
        <Badge variant="outline" className={cn('capitalize', badgeColor)}>
          {isReviewed ? 'Reviewed' : 'Unreviewed'}
        </Badge>
      )
    },
    filterFn: (row, id, value) => value.includes(row.getValue(id)),
    enableSorting: false,
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Received" />
    ),
    cell: ({ row }) => <div>{row.getValue('created_at')}</div>,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
