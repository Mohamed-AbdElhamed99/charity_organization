import { useCallback, useEffect, useState } from 'react'
import { Link, router } from '@inertiajs/react'
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
import { create, index as beneficiariesIndex } from '@/routes/admin/beneficiaries'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import type { BeneficiaryListItem, SelectOption } from '@/types/models/beneficiary'
import type { Paginated } from '@/types/pagination'
import { optionsFromServer } from './data/data'
import { beneficiariesColumns as columns } from './beneficiaries-columns'

type BeneficiariesTableProps = {
  beneficiaries: Paginated<BeneficiaryListItem>
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  search: Record<string, unknown>
}

export function BeneficiariesTable({
  beneficiaries,
  typeOptions,
  statusOptions,
  search,
}: BeneficiariesTableProps) {
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
        beneficiariesIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['beneficiaries', 'search'],
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
        { columnId: 'type', searchKey: 'type', type: 'array' },
        { columnId: 'status', searchKey: 'status', type: 'array' },
      ],
    })

  const table = useReactTable({
    data: beneficiaries.data,
    columns,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
    },
    manualFiltering: true,
    manualPagination: true,
    pageCount: beneficiaries.last_page,
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
    ensurePageInRange(beneficiaries.last_page)
  }, [beneficiaries.last_page, ensurePageInRange])

  const typeFilterOptions = optionsFromServer(typeOptions).map((option) => ({
    label: option.label,
    value: option.value,
  }))

  const statusFilterOptions = optionsFromServer(statusOptions).map(
    (option) => ({
      label: option.label,
      value: option.value,
    })
  )

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder="Search beneficiaries..."
        searchKey="query"
        filters={[
          {
            columnId: 'type',
            title: 'Type',
            options: typeFilterOptions,
          },
          {
            columnId: 'status',
            title: 'Status',
            options: statusFilterOptions,
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
                    if (cell.column.id === 'query') {
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
                  colSpan={columns.length - 1}
                  className="h-32 text-center"
                >
                  <div className="flex flex-col items-center gap-3">
                    <p className="text-muted-foreground">
                      No beneficiaries found.
                    </p>
                    <Button asChild size="sm">
                      <Link href={create.url()}>New beneficiary</Link>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {beneficiaries.from && beneficiaries.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {beneficiaries.from}–{beneficiaries.to} of{' '}
          {beneficiaries.total} beneficiaries
        </p>
      )}

      <DataTablePagination
        pagination={beneficiaries}
        search={search}
        indexUrl={beneficiariesIndex.url()}
        defaultPerPage={20}
        className="mt-auto"
      />
    </div>
  )
}
