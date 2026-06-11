import { type ColumnDef } from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { DataTableColumnHeader } from '@/components/data-table'
import { formatMoney } from '@/components/admin/transactions/data/data'
import type { GeneralExpense } from '@/types/models/general-expense'
import { DataTableRowActions } from './data-table-row-actions'

export const generalExpensesColumns: ColumnDef<GeneralExpense>[] = [
  {
    id: 'query',
    accessorFn: (row) =>
      `${row.name} ${row.category_name ?? ''} ${row.vendor_name ?? ''} ${row.transaction?.payment_method_name ?? ''}`,
    enableHiding: true,
    enableSorting: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Name" />
    ),
    cell: ({ row }) => (
      <div className="font-medium">{row.original.name}</div>
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
        {formatMoney(
          row.original.amount,
          row.original.transaction?.currency_symbol ?? undefined
        )}
      </div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'expense_date',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Date" />
    ),
    cell: ({ row }) => (
      <div className="whitespace-nowrap">{row.original.expense_date ?? '—'}</div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'category_name',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Category" />
    ),
    cell: ({ row }) => <div>{row.original.category_name ?? '—'}</div>,
    enableSorting: false,
  },
  {
    id: 'payment_method',
    accessorFn: (row) => row.transaction?.payment_method_name ?? '',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Payment Method" />
    ),
    cell: ({ row }) => (
      <div>{row.original.transaction?.payment_method_name ?? '—'}</div>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'is_recurring',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Recurring" />
    ),
    cell: ({ row }) => {
      const isRecurring = row.original.is_recurring

      return (
        <Badge
          variant="outline"
          className={cn(
            'capitalize',
            isRecurring
              ? 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300'
              : 'text-muted-foreground'
          )}
        >
          {isRecurring ? 'Recurring' : 'One-time'}
        </Badge>
      )
    },
    enableSorting: false,
  },
  {
    id: 'actions',
    cell: DataTableRowActions,
  },
]
