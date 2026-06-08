import { type ColumnDef } from '@tanstack/react-table'
import { ArrowDownLeft, ArrowUpRight } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { cn } from '@/lib/utils'
import { DataTableRowActions } from './data-table-row-actions'
import { formatMoney } from './data/data'
import type { Transaction } from '@/types/models/transaction'

type TransactionsColumnsOptions = {
  onReverse: (transaction: Transaction) => void
}

export function createTransactionsColumns({
  onReverse,
}: TransactionsColumnsOptions): ColumnDef<Transaction>[] {
  return [
    {
      id: 'type',
      accessorKey: 'transaction_type',
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'direction',
      accessorKey: 'direction',
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'account_id',
      accessorKey: 'account_id',
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'date_from',
      accessorKey: 'date_from',
      enableHiding: true,
      enableSorting: false,
    },
    {
      id: 'date_to',
      accessorKey: 'date_to',
      enableHiding: true,
      enableSorting: false,
    },
    {
      accessorKey: 'transaction_date',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Date" />
      ),
      cell: ({ row }) => (
        <div className="whitespace-nowrap">
          {row.original.transaction_date ?? '—'}
        </div>
      ),
    },
    {
      accessorKey: 'transaction_type_label',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Type" />
      ),
      cell: ({ row }) => (
        <Badge variant="outline" className="whitespace-nowrap">
          {row.original.transaction_type_label ?? '—'}
        </Badge>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'direction',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Direction" />
      ),
      cell: ({ row }) => {
        const direction = row.original.direction

        return (
          <Badge
            variant="outline"
            className={cn(
              'gap-1 capitalize',
              direction === 'in'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                : 'border-rose-200 bg-rose-50 text-rose-800 dark:bg-rose-950 dark:text-rose-200'
            )}
          >
            {direction === 'in' ? (
              <ArrowDownLeft className="size-3" />
            ) : (
              <ArrowUpRight className="size-3" />
            )}
            {direction ?? '—'}
          </Badge>
        )
      },
      enableSorting: false,
    },
    {
      id: 'account',
      accessorFn: (row) => row.account?.name ?? row.account_id,
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Account" />
      ),
      cell: ({ row }) => (
        <div>{row.original.account?.name ?? `#${row.original.account_id}`}</div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'description',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Description" />
      ),
      cell: ({ row }) => (
        <div className="max-w-60 truncate" title={row.original.description ?? ''}>
          {row.original.description ?? '—'}
        </div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'net_amount',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Net Amount" />
      ),
      cell: ({ row }) => (
        <div className="font-medium tabular-nums">
          {formatMoney(
            row.original.net_amount,
            row.original.currency?.symbol
          )}
        </div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'running_balance',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Balance" />
      ),
      cell: ({ row }) => (
        <div className="tabular-nums text-muted-foreground">
          {formatMoney(
            row.original.running_balance,
            row.original.currency?.symbol
          )}
        </div>
      ),
      enableSorting: false,
    },
    {
      id: 'actions',
      cell: ({ row }) => (
        <DataTableRowActions row={row} onReverse={onReverse} />
      ),
    },
  ]
}
