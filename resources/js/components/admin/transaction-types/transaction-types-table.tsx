import { useCallback, useEffect, useState } from 'react'
import { router } from '@inertiajs/react'
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
import { index as transactionTypesIndex } from '@/routes/admin/transaction-types'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import { statusOptions } from './data/data'
import { type TransactionType } from '@/types/models/transaction-type'
import { type Paginated } from '@/types/pagination'
import { DataTableBulkActions } from './data-table-bulk-actions'
import { transactionTypesColumns as columns } from './transaction-types-columns'

type TransactionTypesTableProps = {
  transactionTypes: Paginated<TransactionType>
  search: Record<string, unknown>
}

export function TransactionTypesTable({
  transactionTypes,
  search,
}: TransactionTypesTableProps) {
  const [rowSelection, setRowSelection] = useState({})
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    query: false,
  })
  const [sorting, setSorting] = useState<SortingState>([])

  const navigate: NavigateFn = useCallback(
    ({ search: nextSearch, replace }) => {
      const resolved =
        nextSearch === true
          ? {}
          : typeof nextSearch === 'function'
            ? nextSearch(search)
            : nextSearch

      router.get(
        transactionTypesIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['transactionTypes', 'search'],
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
        { columnId: 'query', searchKey: 'query', type: 'string' },
        { columnId: 'status', searchKey: 'status', type: 'array' },
      ],
    })

  const table = useReactTable({
    data: transactionTypes.data,
    columns,
    state: {
      sorting,
      rowSelection,
      columnFilters,
      columnVisibility,
    },
    manualFiltering: true,
    enableRowSelection: (row) => !row.original.is_system,
    onColumnFiltersChange,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  useEffect(() => {
    ensurePageInRange(transactionTypes.last_page)
  }, [transactionTypes.last_page, ensurePageInRange])

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder="Filter transaction types..."
        searchKey="query"
        filters={[
          {
            columnId: 'status',
            title: 'Status',
            options: statusOptions.map((option) => ({
              label: option.label,
              value: option.value,
            })),
          },
        ]}
      />

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id} className="group/row">
                {headerGroup.headers.map((header) => {
                  if (header.column.id === 'query') {
                    return null
                  }

                  return (
                    <TableHead
                      key={header.id}
                      colSpan={header.colSpan}
                      className={cn(
                        'bg-background group-hover/row:bg-muted group-data-[state=selected]/row:bg-muted',
                        (
                          header.column.columnDef.meta as
                            | { className?: string; thClassName?: string }
                            | undefined
                        )?.className,
                        (
                          header.column.columnDef.meta as
                            | { className?: string; thClassName?: string }
                            | undefined
                        )?.thClassName
                      )}
                    >
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
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className="group/row"
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell
                      key={cell.id}
                      className={cn(
                        'bg-background group-hover/row:bg-muted group-data-[state=selected]/row:bg-muted',
                        (
                          cell.column.columnDef.meta as
                            | { className?: string; tdClassName?: string }
                            | undefined
                        )?.className,
                        (
                          cell.column.columnDef.meta as
                            | { className?: string; tdClassName?: string }
                            | undefined
                        )?.tdClassName
                      )}
                    >
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length - 1}
                  className="h-24 text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {transactionTypes.from && transactionTypes.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {transactionTypes.from}–{transactionTypes.to} of{' '}
          {transactionTypes.total} transaction types
        </p>
      )}

      <DataTablePagination
        pagination={transactionTypes}
        search={search}
        indexUrl={transactionTypesIndex.url()}
        defaultPerPage={20}
        className="mt-auto"
      />
      <DataTableBulkActions table={table} />
    </div>
  )
}
