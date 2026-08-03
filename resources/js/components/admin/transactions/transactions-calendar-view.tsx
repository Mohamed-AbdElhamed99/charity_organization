import { useCallback, useMemo, useState } from 'react'
import { router } from '@inertiajs/react'
import { Calculator, ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/lib/utils'
import { buildTableQueryParams } from '@/lib/table-query'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import { Button } from '@/components/ui/button'
import { TransactionsTable } from './transactions-table'
import type { AccountOption, Transaction } from '@/types/models/transaction'
import type { TransactionTypeOption } from '@/types/models/transaction-type'
import type { Paginated } from '@/types/pagination'

type TransactionsCalendarViewProps = {
  transactions: Paginated<Transaction>
  accounts: AccountOption[]
  transactionTypes: TransactionTypeOption[]
  search: Record<string, unknown>
}

const TAB_COUNT = 7
const MAX_NEXT_MONTHS = 3

function parseMonth(value: unknown): Date {
  if (typeof value === 'string' && /^\d{4}-\d{2}$/.test(value)) {
    const [year, month] = value.split('-').map(Number)
    return new Date(year, month - 1, 1)
  }

  const now = new Date()

  return new Date(now.getFullYear(), now.getMonth(), 1)
}

function formatMonthKey(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')

  return `${year}-${month}`
}

function formatMonthLabel(date: Date): string {
  return date.toLocaleDateString(undefined, {
    month: 'long',
    year: 'numeric',
  })
}

function shiftMonth(date: Date, offset: number): Date {
  return new Date(date.getFullYear(), date.getMonth() + offset, 1)
}

function currentMonthStart(): Date {
  const now = new Date()

  return new Date(now.getFullYear(), now.getMonth(), 1)
}

/** Whole months from `from` to `to` (negative if `to` is before `from`). */
function monthsBetween(from: Date, to: Date): number {
  return (
    (to.getFullYear() - from.getFullYear()) * 12 +
    (to.getMonth() - from.getMonth())
  )
}

/**
 * Always 7 tabs including the selected month.
 * Prefer up to 3 months after selected when those months exist (≤ current calendar month);
 * fill the remaining slots with previous months.
 */
export function buildMonthTabs(
  selected: Date,
  today: Date = currentMonthStart(),
): Date[] {
  const availableNext = Math.max(0, monthsBetween(selected, today))
  const nextCount = Math.min(MAX_NEXT_MONTHS, availableNext)
  const prevCount = TAB_COUNT - 1 - nextCount

  return Array.from({ length: TAB_COUNT }, (_, index) =>
    shiftMonth(selected, index - prevCount),
  )
}

export function TransactionsCalendarView({
  transactions,
  accounts,
  transactionTypes,
  search,
}: TransactionsCalendarViewProps) {
  const [calculating, setCalculating] = useState(false)

  const selectedMonth = useMemo(
    () => parseMonth(search.month),
    [search.month],
  )

  const tabs = useMemo(() => buildMonthTabs(selectedMonth), [selectedMonth])

  const visitMonth = useCallback(
    (month: string) => {
      router.get(
        transactionsIndex.url(),
        buildTableQueryParams(search, {
          view: 'calendar',
          month,
          page: undefined,
          date_from: undefined,
          date_to: undefined,
        }),
        {
          preserveState: true,
          preserveScroll: true,
          only: ['transactions', 'search'],
        },
      )
    },
    [search],
  )

  const calculateBalance = useCallback(() => {
    const month = formatMonthKey(selectedMonth)

    router.post(
      route('admin.transactions.calculate-balance'),
      { month },
      {
        preserveScroll: true,
        onStart: () => setCalculating(true),
        onFinish: () => setCalculating(false),
      },
    )
  }, [selectedMonth])

  const selectedKey = formatMonthKey(selectedMonth)

  return (
    <div className="flex flex-1 flex-col gap-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="grid min-w-0 flex-1 grid-cols-[auto_1fr_auto] items-center gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="h-8"
            aria-label="Previous month"
            onClick={() =>
              visitMonth(formatMonthKey(shiftMonth(selectedMonth, -1)))
            }
          >
            <ChevronLeft className="size-4" />
            Prev
          </Button>

          <div
            className="flex min-w-0 flex-wrap items-center justify-center gap-1 overflow-x-auto"
            role="tablist"
            aria-label="Transaction months"
          >
            {tabs.map((monthDate) => {
              const key = formatMonthKey(monthDate)
              const isActive = key === selectedKey

              return (
                <Button
                  key={key}
                  type="button"
                  role="tab"
                  aria-selected={isActive}
                  variant={isActive ? 'default' : 'ghost'}
                  size="sm"
                  className={cn(
                    'h-8 shrink-0',
                    isActive && 'pointer-events-none',
                  )}
                  onClick={() => visitMonth(key)}
                >
                  {formatMonthLabel(monthDate)}
                </Button>
              )
            })}
          </div>

          <Button
            type="button"
            variant="outline"
            size="sm"
            className="h-8"
            aria-label="Next month"
            onClick={() =>
              visitMonth(formatMonthKey(shiftMonth(selectedMonth, 1)))
            }
          >
            Next
            <ChevronRight className="size-4" />
          </Button>
        </div>

        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8 shrink-0"
          disabled={calculating}
          onClick={calculateBalance}
        >
          <Calculator className="size-4" />
          {calculating ? 'Calculating…' : 'Calculate Balance'}
        </Button>
      </div>

      <TransactionsTable
        transactions={transactions}
        accounts={accounts}
        transactionTypes={transactionTypes}
        search={search}
        mode="calendar"
      />
    </div>
  )
}
