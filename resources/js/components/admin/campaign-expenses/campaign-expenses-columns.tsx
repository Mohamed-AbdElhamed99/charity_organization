import { type ColumnDef } from '@tanstack/react-table'
import { DataTableColumnHeader } from '@/components/data-table'
import { formatExpenseAmount } from './data/data'
import type { CampaignExpense } from '@/types/models/campaign-expense'

export function createCampaignExpensesColumns(
  showCampaignColumn: boolean
): ColumnDef<CampaignExpense>[] {
  const columns: ColumnDef<CampaignExpense>[] = [
    {
      id: 'query',
      accessorKey: 'item_name',
      enableHiding: true,
      enableSorting: false,
    },
    {
      accessorKey: 'expense_date',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Date" />
      ),
      cell: ({ row }) => (
        <div className="whitespace-nowrap">{row.original.expense_date}</div>
      ),
    },
  ]

  if (showCampaignColumn) {
    columns.push({
      accessorKey: 'campaign_name',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Campaign" />
      ),
      cell: ({ row }) => (
        <div>{row.original.campaign_name ?? '—'}</div>
      ),
      enableSorting: false,
    })
  }

  columns.push(
    {
      accessorKey: 'item_name',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Item" />
      ),
      cell: ({ row }) => <div>{row.original.item_name ?? '—'}</div>,
      enableSorting: false,
    },
    {
      accessorKey: 'quantity',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Qty" />
      ),
      cell: ({ row }) => (
        <div className="tabular-nums">{row.original.quantity}</div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'item_price',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Unit Price" />
      ),
      cell: ({ row }) => (
        <div className="tabular-nums">
          {formatExpenseAmount(row.original.item_price)}
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
          {formatExpenseAmount(row.original.amount)}
        </div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'responsible_user_name',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Responsible" />
      ),
      cell: ({ row }) => (
        <div>{row.original.responsible_user_name ?? '—'}</div>
      ),
      enableSorting: false,
    },
    {
      accessorKey: 'residual_amount',
      header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Residual" />
      ),
      cell: ({ row }) => (
        <div className="tabular-nums text-muted-foreground">
          {formatExpenseAmount(row.original.residual_amount)}
        </div>
      ),
      enableSorting: false,
    }
  )

  return columns
}
