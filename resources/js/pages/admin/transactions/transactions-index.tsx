import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { TransactionsPrimaryButtons } from '@/components/admin/transactions/transactions-primary-buttons'
import { TransactionsTable } from '@/components/admin/transactions/transactions-table'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import type { AccountOption, Transaction } from '@/types/models/transaction'
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
}

type PageProps = {
  transactions: Paginated<Transaction>
  accounts: AccountOption[]
  search: SearchParams
}

export default function TransactionsIndex() {
  const { transactions, accounts, search } = usePage<PageProps>().props

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

        <TransactionsTable
          transactions={transactions}
          accounts={accounts}
          search={search}
        />
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
