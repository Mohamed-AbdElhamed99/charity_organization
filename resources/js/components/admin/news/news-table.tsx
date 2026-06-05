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
  type ColumnFiltersState,
  type SortingState,
  type VisibilityState,
} from '@tanstack/react-table'
import { cn } from '@/lib/utils'
import { buildTableQueryParams } from '@/lib/table-query'
import { type NavigateFn, useTableUrlState } from '@/hooks/use-table-url-state'
import { index as newsIndex } from '@/routes/admin/news'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import type { News, NewsCategory } from '@/types/models/news'
import type { Paginated } from '@/types/pagination'
import { categoryOptionsFromList, statusOptions } from './data/data'
import { DataTableBulkActions } from './data-table-bulk-actions'
import { newsColumns as columns } from './news-columns'

type NewsTableProps = {
  news: Paginated<News>
  categories: NewsCategory[]
  search: Record<string, unknown>
}

export function NewsTable({ news, categories, search }: NewsTableProps) {
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
        newsIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveScroll: true,
          replace,
        }
      )
    },
    [search]
  )

  const {
    columnFilters,
    onColumnFiltersChange,
    ensurePageInRange,
  } = useTableUrlState({
    search,
    navigate,
    globalFilter: { enabled: false },
    columnFilters: [
      { columnId: 'query', searchKey: 'query', type: 'string' },
      { columnId: 'status', searchKey: 'status', type: 'array' },
      { columnId: 'category_name', searchKey: 'category', type: 'array' },
    ],
  })

  const table = useReactTable({
    data: news.data,
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
    ensurePageInRange(news.last_page)
  }, [news.last_page, ensurePageInRange])

  const categoryFilterOptions = categoryOptionsFromList(categories)

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder="Filter news..."
        searchKey="query"
        filters={[
          {
            columnId: 'status',
            title: 'Status',
            options: statusOptions,
          },
          {
            columnId: 'category_name',
            title: 'Category',
            options: categoryFilterOptions,
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
                        (header.column.columnDef.meta as { className?: string; thClassName?: string } | undefined)?.className,
                        (header.column.columnDef.meta as { className?: string; thClassName?: string } | undefined)?.thClassName
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
                        (cell.column.columnDef.meta as { className?: string; tdClassName?: string } | undefined)?.className,
                        (cell.column.columnDef.meta as { className?: string; tdClassName?: string } | undefined)?.tdClassName
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
                <TableCell colSpan={columns.length - 1} className="h-24 text-center">
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {news.from && news.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {news.from}–{news.to} of {news.total} news articles
        </p>
      )}

      <DataTablePagination
        pagination={news}
        search={search}
        indexUrl={newsIndex.url()}
        defaultPerPage={25}
        className="mt-auto"
      />
      <DataTableBulkActions table={table} />
    </div>
  )
}
