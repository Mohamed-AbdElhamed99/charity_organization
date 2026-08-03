import { Head, router, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { TransactionsCalendarView } from '@/components/admin/transactions/transactions-calendar-view'
import { TransactionsPrimaryButtons } from '@/components/admin/transactions/transactions-primary-buttons'
import { TransactionsTable } from '@/components/admin/transactions/transactions-table'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { buildTableQueryParams } from '@/lib/table-query'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import type { AccountOption, Transaction } from '@/types/models/transaction'
import type { TransactionTypeOption } from '@/types/models/transaction-type'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  type?: string | string[]
  direction?: string | string[]
  account_id?: string
  date_from?: string
  date_to?: string
  campaign_id?: string
  page?: number
  per_page?: number
  view?: 'list' | 'calendar' | string
  month?: string
}

type PageProps = {
  transactions: Paginated<Transaction>
  accounts: AccountOption[]
  transactionTypes: TransactionTypeOption[]
  search: SearchParams
}

function currentMonthKey(): string {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')

  return `${year}-${month}`
}

export default function TransactionsIndex() {
  const { transactions, accounts, transactionTypes, search } =
    usePage<PageProps>().props

  const view = search.view === 'calendar' ? 'calendar' : 'list'

  const switchView = (nextView: string) => {
    if (nextView !== 'list' && nextView !== 'calendar') {
      return
    }

    if (nextView === view) {
      return
    }

    if (nextView === 'calendar') {
      router.get(
        transactionsIndex.url(),
        buildTableQueryParams(search, {
          view: 'calendar',
          month:
            typeof search.month === 'string' && search.month !== ''
              ? search.month
              : currentMonthKey(),
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

      return
    }

    router.get(
      transactionsIndex.url(),
      buildTableQueryParams(
        {
          ...search,
          month: undefined,
          date_from: undefined,
          date_to: undefined,
        },
        {
          view: 'list',
          page: undefined,
        },
      ),
      {
        preserveState: true,
        preserveScroll: true,
        only: ['transactions', 'search'],
      },
    )
  }

  return (
    <>
      <Head title="Transactions" />

      <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
        <div className="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Transactions</h2>
            <p className="text-muted-foreground">
              Ledger of all financial movements with filters and account
              statement export.
            </p>
          </div>
          <TransactionsPrimaryButtons />
        </div>

        <ToggleGroup
          type="single"
          value={view}
          onValueChange={switchView}
          variant="outline"
          size="sm"
          className="w-fit"
          aria-label="Transactions view"
        >
          <ToggleGroupItem value="list" aria-label="List Transactions">
            List Transactions
          </ToggleGroupItem>
          <ToggleGroupItem value="calendar" aria-label="Calendar View">
            Calendar View
          </ToggleGroupItem>
        </ToggleGroup>

        {view === 'calendar' ? (
          <TransactionsCalendarView
            transactions={transactions}
            accounts={accounts}
            transactionTypes={transactionTypes}
            search={search}
          />
        ) : (
          <TransactionsTable
            transactions={transactions}
            accounts={accounts}
            transactionTypes={transactionTypes}
            search={search}
            mode="list"
          />
        )}
      </Main>
    </>
  )
}

TransactionsIndex.layout = {
  breadcrumbs: [
    {
      title: 'Transactions',
      href: transactionsIndex.url(),
    },
  ],
}
