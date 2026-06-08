import { type ColumnDef } from '@tanstack/react-table'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { formatTransferAmount } from './data/data'
import type { Transfer } from '@/types/models/transfer'

export const transfersColumns: ColumnDef<Transfer>[] = [
  {
    id: 'query',
    accessorKey: 'recipient_name',
    enableHiding: true,
    enableSorting: false,
  },
  {
    id: 'recipient_type',
    accessorKey: 'recipient_type',
    enableHiding: true,
    enableSorting: false,
  },
  {
    id: 'campaign_id',
    accessorKey: 'campaign_id',
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'transfer_date',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Date" />
    ),
    cell: ({ row }) => (
      <div className="whitespace-nowrap">
        {row.original.transfer_date ?? '—'}
      </div>
    ),
  },
  {
    accessorKey: 'recipient_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Recipient" />
    ),
    cell: ({ row }) => (
      <div>
        <div className="font-medium">{row.original.recipient_name}</div>
        {row.original.recipient_phone && (
          <div className="text-xs text-muted-foreground">
            {row.original.recipient_phone}
          </div>
        )}
      </div>
    ),
    enableHiding: false,
  },
  {
    accessorKey: 'recipient_type_label',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Type" />
    ),
    cell: ({ row }) => (
      <Badge variant="outline">
        {row.original.recipient_type_label ?? '—'}
      </Badge>
    ),
    enableSorting: false,
  },
  {
    id: 'campaign',
    accessorFn: (row) => row.campaign?.title_en ?? row.campaign_id,
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Campaign" />
    ),
    cell: ({ row }) => (
      <div>
        {row.original.campaign?.title_en ?? (
          <span className="text-muted-foreground">—</span>
        )}
      </div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'purpose',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Purpose" />
    ),
    cell: ({ row }) => (
      <div className="max-w-48 truncate" title={row.original.purpose}>
        {row.original.purpose}
      </div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'amount',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Amount" />
    ),
    cell: ({ row }) => (
      <div className="font-medium tabular-nums">
        {formatTransferAmount(row.original.amount)}
      </div>
    ),
    enableSorting: false,
  },
]
