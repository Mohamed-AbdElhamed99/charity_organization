import { useCallback, useEffect, useMemo, useState } from 'react'
import { router } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Download } from 'lucide-react'
import {
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getSortedRowModel,
  useReactTable,
  type SortingState,
  type VisibilityState,
} from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { buildTableQueryParams } from '@/lib/table-query'
import { type NavigateFn, useTableUrlState } from '@/hooks/use-table-url-state'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import { createTransactionsColumns } from './transactions-columns'
import { TransactionsReverseDialog } from './transactions-reverse-dialog'
import { transactionDirectionOptions } from './data/data'
import type { AccountOption, Transaction } from '@/types/models/transaction'
import type { TransactionTypeOption } from '@/types/models/transaction-type'
import type { Paginated } from '@/types/pagination'

type TransactionsTableProps = {
  transactions: Paginated<Transaction>
  accounts: AccountOption[]
  transactionTypes: TransactionTypeOption[]
  search: Record<string, unknown>
  mode?: 'list' | 'calendar'
}

function toDateInputValue(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function monthRange(offsetMonths: number): { from: string; to: string } {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth() + offsetMonths, 1)
  const end = new Date(now.getFullYear(), now.getMonth() + offsetMonths + 1, 0)

  return {
    from: toDateInputValue(start),
    to: toDateInputValue(end),
  }
}

function buildExportUrl(search: Record<string, unknown>): string {
  const params = buildTableQueryParams(search, {}, { perPage: 20 })
  delete params.page
  delete params.per_page

  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (Array.isArray(value)) {
      value.forEach((item) => query.append(key, String(item)))
      continue
    }

    query.set(key, String(value))
  }

  const baseUrl = route('admin.transactions.export')
  const queryString = query.toString()

  return queryString ? `${baseUrl}?${queryString}` : baseUrl
}

export function TransactionsTable({
  transactions,
  accounts,
  transactionTypes,
  search,
  mode = 'list',
}: TransactionsTableProps) {
  const isCalendarMode = mode === 'calendar'
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    type: false,
    direction: false,
    account_id: false,
    date_from: false,
    date_to: false,
  })
  const [sorting, setSorting] = useState<SortingState>([])
  const [reverseTarget, setReverseTarget] = useState<Transaction | null>(null)
  const [reverseOpen, setReverseOpen] = useState(false)

  const navigate: NavigateFn = useCallback(
    ({ search: nextSearch, replace }) => {
      const resolved =
        nextSearch === true
          ? {}
          : typeof nextSearch === 'function'
            ? nextSearch(search)
            : nextSearch

      router.get(
        transactionsIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['transactions', 'search'],
        }
      )
    },
    [search]
  )

  const { columnFilters, onColumnFiltersChange, ensurePageInRange } =
    useTableUrlState({
      search,
      navigate,
      globalFilter: { enabled: false },
      columnFilters: [
        { columnId: 'type', searchKey: 'type', type: 'array' },
        { columnId: 'direction', searchKey: 'direction', type: 'array' },
        {
          columnId: 'account_id',
          searchKey: 'account_id',
          type: 'array',
          serialize: (value) => {
            const values = Array.isArray(value) ? value : []
            return values.length > 0 ? String(values[0]) : undefined
          },
          deserialize: (value) => {
            if (typeof value === 'string' && value.trim() !== '') {
              return [value]
            }

            if (Array.isArray(value)) {
              return value.map(String)
            }

            return []
          },
        },
        { columnId: 'date_from', searchKey: 'date_from', type: 'string' },
        { columnId: 'date_to', searchKey: 'date_to', type: 'string' },
      ],
    })

  const applyDateRange = useCallback(
    (from: string, to: string) => {
      onColumnFiltersChange((previous) => {
        const withoutDates = previous.filter(
          (filter) => filter.id !== 'date_from' && filter.id !== 'date_to',
        )

        return [
          ...withoutDates,
          { id: 'date_from', value: from },
          { id: 'date_to', value: to },
        ]
      })
    },
    [onColumnFiltersChange],
  )

  const columns = useMemo(
    () =>
      createTransactionsColumns({
        onReverse: (transaction) => {
          setReverseTarget(transaction)
          setReverseOpen(true)
        },
      }),
    []
  )

  const accountFilterOptions = useMemo(
    () =>
      accounts.map((account) => ({
        label: account.name,
        value: String(account.id),
      })),
    [accounts]
  )

  const table = useReactTable({
    data: transactions.data,
    columns,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
    },
    manualFiltering: true,
    onColumnFiltersChange,
    onSortingChange: setSorting,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  const dateFrom =
    (table.getColumn('date_from')?.getFilterValue() as string) ?? ''
  const dateTo = (table.getColumn('date_to')?.getFilterValue() as string) ?? ''

  const thisMonthRange = useMemo(() => monthRange(0), [])
  const lastMonthRange = useMemo(() => monthRange(-1), [])

  const isThisMonthActive =
    dateFrom === thisMonthRange.from && dateTo === thisMonthRange.to
  const isLastMonthActive =
    dateFrom === lastMonthRange.from && dateTo === lastMonthRange.to

  useEffect(() => {
    ensurePageInRange(transactions.last_page)
  }, [transactions.last_page, ensurePageInRange])

  const hiddenFilterColumns = new Set([
    'type',
    'direction',
    'account_id',
    'date_from',
    'date_to',
  ])

  return (
    <>
      <div className="flex flex-1 flex-col gap-4">
        <div className="flex flex-wrap items-end justify-between gap-3">
          <div className="flex flex-1 flex-col gap-3">
            <DataTableToolbar
              table={table}
              searchPlaceholder=""
              filters={[
                {
                  columnId: 'type',
                  title: 'Type',
                  options: transactionTypes.map((option) => ({
                    label: option.name,
                    value: option.code,
                  })),
                },
                {
                  columnId: 'direction',
                  title: 'Direction',
                  options: transactionDirectionOptions.map((option) => ({
                    label: option.label,
                    value: option.value,
                  })),
                },
                {
                  columnId: 'account_id',
                  title: 'Account',
                  options: accountFilterOptions,
                },
              ]}
            />

            {!isCalendarMode && (
              <div className="flex flex-wrap items-end gap-3">
                <div className="grid gap-1.5">
                  <Label
                    htmlFor="date_from"
                    className="text-xs text-muted-foreground"
                  >
                    From date
                  </Label>
                  <Input
                    id="date_from"
                    type="date"
                    value={dateFrom}
                    onChange={(event) =>
                      table
                        .getColumn('date_from')
                        ?.setFilterValue(event.target.value)
                    }
                    className="h-8 w-40"
                  />
                </div>
                <div className="grid gap-1.5">
                  <Label
                    htmlFor="date_to"
                    className="text-xs text-muted-foreground"
                  >
                    To date
                  </Label>
                  <Input
                    id="date_to"
                    type="date"
                    value={dateTo}
                    onChange={(event) =>
                      table
                        .getColumn('date_to')
                        ?.setFilterValue(event.target.value)
                    }
                    className="h-8 w-40"
                  />
                </div>
                <div className="flex flex-wrap gap-2">
                  <Button
                    type="button"
                    variant={isThisMonthActive ? 'default' : 'outline'}
                    size="sm"
                    className="h-8"
                    onClick={() =>
                      applyDateRange(thisMonthRange.from, thisMonthRange.to)
                    }
                  >
                    This month
                  </Button>
                  <Button
                    type="button"
                    variant={isLastMonthActive ? 'default' : 'outline'}
                    size="sm"
                    className="h-8"
                    onClick={() =>
                      applyDateRange(lastMonthRange.from, lastMonthRange.to)
                    }
                  >
                    Last month
                  </Button>
                </div>
              </div>
            )}
          </div>

          <Button
            variant="outline"
            size="sm"
            className="h-8 gap-1"
            onClick={() => {
              window.location.href = buildExportUrl(search)
            }}
          >
            <Download className="size-3.5" />
            Export CSV
          </Button>
        </div>

        <div className="overflow-hidden rounded-md border">
          <Table>
            <TableHeader>
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id} className="group/row">
                  {headerGroup.headers.map((header) => {
                    if (hiddenFilterColumns.has(header.column.id)) {
                      return null
                    }

                    return (
                      <TableHead key={header.id} colSpan={header.colSpan}>
                        {header.isPlaceholder
                          ? null
                          : flexRender(
                              header.column.columnDef.header,
                              header.getContext()
                            )}
                      </TableHead>
                    )
                  })}
                </TableRow>
              ))}
            </TableHeader>

            <TableBody>
              {table.getRowModel().rows?.length ? (
                table.getRowModel().rows.map((row) => (
                  <TableRow key={row.id} className="group/row">
                    {row.getVisibleCells().map((cell) => {
                      if (hiddenFilterColumns.has(cell.column.id)) {
                        return null
                      }

                      return (
                        <TableCell key={cell.id}>
                          {flexRender(
                            cell.column.columnDef.cell,
                            cell.getContext()
                          )}
                        </TableCell>
                      )
                    })}
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell
                    colSpan={columns.length - hiddenFilterColumns.size}
                    className="h-24 text-center"
                  >
                    No results.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>

        {transactions.from && transactions.to && (
          <p className={cn('px-1 text-sm text-muted-foreground')}>
            Showing {transactions.from}–{transactions.to} of{' '}
            {transactions.total} transactions
          </p>
        )}

        <DataTablePagination
          pagination={transactions}
          search={search}
          indexUrl={transactionsIndex.url()}
          defaultPerPage={20}
          className="mt-auto"
        />
      </div>

      <TransactionsReverseDialog
        open={reverseOpen}
        onOpenChange={(open) => {
          setReverseOpen(open)
          if (!open) {
            setReverseTarget(null)
          }
        }}
        transaction={reverseTarget}
      />
    </>
  )
}
