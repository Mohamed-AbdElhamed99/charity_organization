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
import { index as generalExpenseCategoriesIndex } from '@/routes/admin/general-expense-categories'
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
import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'
import { type Paginated } from '@/types/pagination'
import { DataTableBulkActions } from './data-table-bulk-actions'
import { generalExpenseCategoriesColumns as columns } from './general-expense-categories-columns'

type GeneralExpenseCategoriesTableProps = {
  categories: Paginated<GeneralExpenseCategory>
  search: Record<string, unknown>
}

export function GeneralExpenseCategoriesTable({
  categories,
  search,
}: GeneralExpenseCategoriesTableProps) {
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
        generalExpenseCategoriesIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['categories', 'search'],
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
    data: categories.data,
    columns,
    state: {
      sorting,
      rowSelection,
      columnFilters,
      columnVisibility,
    },
    manualFiltering: true,
    enableRowSelection: true,
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
    ensurePageInRange(categories.last_page)
  }, [categories.last_page, ensurePageInRange])

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder="Filter categories..."
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

      {categories.from && categories.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {categories.from}–{categories.to} of {categories.total}{' '}
          categories
        </p>
      )}

      <DataTablePagination
        pagination={categories}
        search={search}
        indexUrl={generalExpenseCategoriesIndex.url()}
        defaultPerPage={20}
        className="mt-auto"
      />
      <DataTableBulkActions table={table} />
    </div>
  )
}
