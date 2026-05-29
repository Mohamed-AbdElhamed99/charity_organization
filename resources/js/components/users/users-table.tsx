import { useEffect, useState } from 'react'
import { router } from '@inertiajs/react'
import { cn } from '@/lib/utils'
import { type NavigateFn, useTableUrlState } from '@/hooks/use-table-url-state'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import { User } from '@/types/models/user'
import { Paginated } from '@/types/pagination'
import { DataTableBulkActions } from './data-table-bulk-actions'
import { usersColumns as columns } from './users-columns'

type DataTableProps = {
  data: Paginated<User[]>       // ← was User[]
  search: Record<string, unknown>
  navigate: NavigateFn
}

export function UsersTable({ data, search, navigate }: DataTableProps) {
  const [rowSelection, setRowSelection] = useState({})
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})
  const [sorting, setSorting] = useState<SortingState>([])

  const {
    columnFilters,
    onColumnFiltersChange,
    pagination,
    onPaginationChange,
    ensurePageInRange,
  } = useTableUrlState({
    search,
    navigate,
    pagination: { defaultPage: 1, defaultPageSize: data.per_page },  // ← use backend per_page
    globalFilter: { enabled: false },
    columnFilters: [
      { columnId: 'username', searchKey: 'username', type: 'string' },
      { columnId: 'status',   searchKey: 'status',   type: 'array' },
      { columnId: 'role',     searchKey: 'role',      type: 'array' },
    ],
  })

  const table = useReactTable({
    data: data.data,                      // ← unwrap the data array
    columns,
    state: {
      sorting,
      pagination: {
        pageIndex: data.current_page - 1, // ← TanStack is 0-based, Laravel is 1-based
        pageSize: data.per_page,
      },
      rowSelection,
      columnFilters,
      columnVisibility,
    },

    // ─── Server-side pagination ───────────────────────────────────────────
    manualPagination: true,               // ← tell TanStack: don't slice data yourself
    pageCount: data.last_page,            // ← total pages from Laravel
    onPaginationChange: (updater) => {
      const next =
        typeof updater === 'function'
          ? updater({ pageIndex: data.current_page - 1, pageSize: data.per_page })
          : updater

      // Navigate to the new page via Inertia, preserving other query params
      router.get(
        window.location.pathname,
        { ...search, page: next.pageIndex + 1 },
        { preserveState: true, preserveScroll: true }
      )
    },

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
    // getPaginationRowModel is intentionally removed — server handles it
  })

  useEffect(() => {
    ensurePageInRange(data.last_page)     // ← use backend page count
  }, [data.last_page, ensurePageInRange])

  return (
    <div
      className={cn(
        'max-sm:has-[div[role="toolbar"]]:mb-16',
        'flex flex-1 flex-col gap-4'
      )}
    >
      <DataTableToolbar
        table={table}
        searchPlaceholder='Filter users...'
        searchKey='username'
        filters={[
          {
            columnId: 'status',
            title: 'Status',
            options: [
              { label: 'Active',     value: 'active' },
              { label: 'Inactive',   value: 'inactive' },
              { label: 'Invited',    value: 'invited' },
              { label: 'Suspended',  value: 'suspended' },
            ],
          },
          // {
          //   columnId: 'role',
          //   title: 'Role',
          //   options: roles.map((role:any) => ({ ...role })),
          // },
        ]}
      />

      <div className='overflow-hidden rounded-md border'>
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup:any) => (
              <TableRow key={headerGroup.id} className='group/row'>
                {headerGroup.headers.map((header:any) => (
                  <TableHead
                    key={header.id}
                    colSpan={header.colSpan}
                    className={cn(
                      'bg-background group-hover/row:bg-muted group-data-[state=selected]/row:bg-muted',
                      header.column.columnDef.meta?.className,
                      header.column.columnDef.meta?.thClassName
                    )}
                  >
                    {/* {header.isPlaceholder
                      ? null
                      : flexRender(header.column.columnDef.header, header.getContext())} */}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>

          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row:any) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className='group/row'
                >
                  {row.getVisibleCells().map((cell:any) => (
                    <TableCell
                      key={cell.id}
                      className={cn(
                        'bg-background group-hover/row:bg-muted group-data-[state=selected]/row:bg-muted',
                        cell.column.columnDef.meta?.className,
                        cell.column.columnDef.meta?.tdClassName
                      )}
                    >
                      {/* {flexRender(cell.column.columnDef.cell, cell.getContext())} */}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className='h-24 text-center'>
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Optional: show "Showing X–Y of Z results" using Laravel meta */}
      {data.from && data.to && (
        <p className='text-sm text-muted-foreground px-1'>
          Showing {data.from}–{data.to} of {data.total} users
        </p>
      )}

      <DataTablePagination table={table} className='mt-auto' />
      <DataTableBulkActions table={table} />
    </div>
  )
}