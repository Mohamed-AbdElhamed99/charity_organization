import { useCallback, useEffect, useMemo, useState } from 'react'
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
import { index as transfersIndex } from '@/routes/admin/transfers'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import {
  campaignOptionsFromList,
  recipientTypeOptions,
} from './data/data'
import { transfersColumns as columns } from './transfers-columns'
import type { CampaignOption, Transfer } from '@/types/models/transfer'
import type { Paginated } from '@/types/pagination'

type TransfersTableProps = {
  transfers: Paginated<Transfer>
  campaigns: CampaignOption[]
  search: Record<string, unknown>
}

export function TransfersTable({
  transfers,
  campaigns,
  search,
}: TransfersTableProps) {
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
    query: false,
    recipient_type: false,
    campaign_id: false,
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
        transfersIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['transfers', 'search'],
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
        { columnId: 'recipient_type', searchKey: 'recipient_type', type: 'array' },
        {
          columnId: 'campaign_id',
          searchKey: 'campaign_id',
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
      ],
    })

  const campaignFilterOptions = useMemo(
    () => campaignOptionsFromList(campaigns),
    [campaigns]
  )

  const table = useReactTable({
    data: transfers.data,
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

  useEffect(() => {
    ensurePageInRange(transfers.last_page)
  }, [transfers.last_page, ensurePageInRange])

  return (
    <div className="flex flex-1 flex-col gap-4">
      <DataTableToolbar
        table={table}
        searchPlaceholder="Filter transfers..."
        searchKey="query"
        filters={[
          {
            columnId: 'recipient_type',
            title: 'Recipient type',
            options: recipientTypeOptions.map((option) => ({
              label: option.label,
              value: option.value,
            })),
          },
          {
            columnId: 'campaign_id',
            title: 'Campaign',
            options: campaignFilterOptions,
          },
        ]}
      />

      <div className="overflow-hidden rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id} className="group/row">
                {headerGroup.headers.map((header) => {
                  if (
                    header.column.id === 'query' ||
                    header.column.id === 'recipient_type' ||
                    header.column.id === 'campaign_id'
                  ) {
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
                    if (
                      cell.column.id === 'query' ||
                      cell.column.id === 'recipient_type' ||
                      cell.column.id === 'campaign_id'
                    ) {
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
                  colSpan={columns.length - 3}
                  className="h-24 text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {transfers.from && transfers.to && (
        <p className={cn('px-1 text-sm text-muted-foreground')}>
          Showing {transfers.from}–{transfers.to} of {transfers.total} transfers
        </p>
      )}

      <DataTablePagination
        pagination={transfers}
        search={search}
        indexUrl={transfersIndex.url()}
        defaultPerPage={20}
        className="mt-auto"
      />
    </div>
  )
}
