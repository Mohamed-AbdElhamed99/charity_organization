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
import { index as donorProfilesIndex } from '@/routes/admin/donor-profiles'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import type {
  DonorProfileListItem,
  SelectOption,
} from '@/types/models/donor-profile'
import type { Paginated } from '@/types/pagination'
import { optionsFromServer } from './data/data'
import { donorProfilesColumns as columns } from './donor-profiles-columns'

type DonorProfilesTableProps = {
  donorProfiles: Paginated<DonorProfileListItem>
  typeOptions: SelectOption[]
  search: Record<string, unknown>
}

export function DonorProfilesTable({
  donorProfiles,
  typeOptions,
  search,
}: DonorProfilesTableProps) {
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
        donorProfilesIndex.url(),
        buildTableQueryParams(search, resolved as Record<string, unknown>),
        {
          preserveState: true,
          preserveScroll: true,
          replace: replace ?? true,
          only: ['donorProfiles', 'search', 'availableUsers'],
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
      ],
    })

  const table = useReactTable({
    data: donorProfiles.data,
    columns,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
    },
    manualFiltering: true,
    manualPagination: true,
    pageCount: donorProfiles.last_page,
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
    ensurePageInRange(donorProfiles.last_page)
  }, [donorProfiles.last_page, ensurePageInRange])

  const typeFilterOptions = optionsFromServer(typeOptions).map((option) => ({
    label: option.label,
    value: option.value,
  }))

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder="Search donor profiles..."
        searchKey="query"
        filters={[
          {
            columnId: 'type',
            title: 'Type',
            options: typeFilterOptions,
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
                  className="h-24 text-center"
                >
                  No donor profiles found.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {donorProfiles.from && donorProfiles.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {donorProfiles.from}–{donorProfiles.to} of{' '}
          {donorProfiles.total} donor profiles
        </p>
      )}

      <DataTablePagination
        pagination={donorProfiles}
        search={search}
        indexUrl={donorProfilesIndex.url()}
        defaultPerPage={20}
        className="mt-auto"
      />
    </div>
  )
}
