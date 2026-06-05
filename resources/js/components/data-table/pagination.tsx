import {
  ChevronLeftIcon,
  ChevronRightIcon,
  DoubleArrowLeftIcon,
  DoubleArrowRightIcon,
} from '@radix-ui/react-icons'
import { router } from '@inertiajs/react'
import { cn, getPageNumbers } from '@/lib/utils'
import { buildTableQueryParams } from '@/lib/table-query'
import { index as usersIndex } from '@/routes/admin/users'
import type { Paginated } from '@/types/pagination'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

type SearchRecord = Record<string, unknown>

type DataTablePaginationProps = {
  pagination: Pick<Paginated<unknown>, 'current_page' | 'last_page' | 'per_page'>
  search: SearchRecord
  defaultPerPage?: number
  className?: string
}

export function DataTablePagination({
  pagination,
  search,
  defaultPerPage = 25,
  className,
}: DataTablePaginationProps) {
  const {
    current_page: currentPage,
    last_page: totalPages,
    per_page: perPage,
  } = pagination
  const pageNumbers = getPageNumbers(currentPage, totalPages)

  const navigate = (patch: SearchRecord) => {
    router.get(
      usersIndex.url(),
      buildTableQueryParams(search, patch, {
        page: 1,
        perPage: defaultPerPage,
      }),
      { preserveScroll: true }
    )
  }

  return (
    <div
      className={cn(
        'flex items-center justify-between overflow-clip px-2',
        '@max-2xl/content:flex-col-reverse @max-2xl/content:gap-4',
        className
      )}
      style={{ overflowClipMargin: 1 }}
    >
      <div className="flex w-full items-center justify-between">
        <div className="flex w-25 items-center justify-center text-sm font-medium @2xl/content:hidden">
          Page {currentPage} of {totalPages}
        </div>
        <div className="flex items-center gap-2 @max-2xl/content:flex-row-reverse">
          <Select
            value={`${perPage}`}
            onValueChange={(value) => {
              navigate({ per_page: Number(value), page: 1 })
            }}
          >
            <SelectTrigger className="h-8 w-17.5">
              <SelectValue placeholder={perPage} />
            </SelectTrigger>
            <SelectContent side="top">
              {[10, 20, 30, 40, 50].map((pageSize) => (
                <SelectItem key={pageSize} value={`${pageSize}`}>
                  {pageSize}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <p className="hidden text-sm font-medium sm:block">Rows per page</p>
        </div>
      </div>

      <div className="flex items-center sm:space-x-6 lg:space-x-8">
        <div className="flex w-25 items-center justify-center text-sm font-medium @max-3xl/content:hidden">
          Page {currentPage} of {totalPages}
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            className="size-8 p-0 @max-md/content:hidden"
            onClick={() => navigate({ page: 1 })}
            disabled={currentPage <= 1}
          >
            <span className="sr-only">Go to first page</span>
            <DoubleArrowLeftIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="size-8 p-0"
            onClick={() => navigate({ page: currentPage - 1 })}
            disabled={currentPage <= 1}
          >
            <span className="sr-only">Go to previous page</span>
            <ChevronLeftIcon className="h-4 w-4" />
          </Button>

          {pageNumbers.map((pageNumber, index) => (
            <div key={`${pageNumber}-${index}`} className="flex items-center">
              {pageNumber === '...' ? (
                <span className="px-1 text-sm text-muted-foreground">...</span>
              ) : (
                <Button
                  variant={currentPage === pageNumber ? 'default' : 'outline'}
                  className="h-8 min-w-8 px-2"
                  onClick={() => navigate({ page: pageNumber })}
                >
                  <span className="sr-only">Go to page {pageNumber}</span>
                  {pageNumber}
                </Button>
              )}
            </div>
          ))}

          <Button
            variant="outline"
            className="size-8 p-0"
            onClick={() => navigate({ page: currentPage + 1 })}
            disabled={currentPage >= totalPages}
          >
            <span className="sr-only">Go to next page</span>
            <ChevronRightIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="size-8 p-0 @max-md/content:hidden"
            onClick={() => navigate({ page: totalPages })}
            disabled={currentPage >= totalPages}
          >
            <span className="sr-only">Go to last page</span>
            <DoubleArrowRightIcon className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  )
}
