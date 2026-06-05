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
import { index as usersIndex } from '@/routes/admin/users'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination, DataTableToolbar } from '@/components/data-table'
import { roleOptionsFromNames } from './data/data'
import { type User } from '@/types/models/user'
import { type Paginated } from '@/types/pagination'
import { DataTableBulkActions } from './data-table-bulk-actions'
import { usersColumns as columns } from './users-columns'

type UsersTableProps = {
  users: Paginated<User>
  roles: string[]
  search: Record<string, unknown>
}

export function UsersTable({ users, roles, search }: UsersTableProps) {
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
        usersIndex.url(),
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
      { columnId: 'role', searchKey: 'role', type: 'array' },
    ],
  })

  const table = useReactTable({
    data: users.data,
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
    ensurePageInRange(users.last_page)
  }, [users.last_page, ensurePageInRange])

  const roleFilterOptions = roleOptionsFromNames(roles).map((role) => ({
    label: role.label,
    value: role.value,
    icon: role.icon,
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
        searchPlaceholder="Filter users..."
        searchKey="query"
        filters={[
          {
            columnId: 'status',
            title: 'Status',
            options: [
              { label: 'Active', value: 'active' },
              { label: 'Inactive', value: 'inactive' },
              { label: 'Banned', value: 'banned' },
            ],
          },
          {
            columnId: 'role',
            title: 'Role',
            options: roleFilterOptions,
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

      {users.from && users.to && (
        <p className="px-1 text-sm text-muted-foreground">
          Showing {users.from}–{users.to} of {users.total} users
        </p>
      )}

      <DataTablePagination
        pagination={users}
        search={search}
        defaultPerPage={25}
        className="mt-auto"
      />
      <DataTableBulkActions table={table} />
    </div>
  )
}
