import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { TransactionTypesDialogs } from '@/components/admin/transaction-types/transaction-types-dialogs'
import { TransactionTypesPrimaryButtons } from '@/components/admin/transaction-types/transaction-types-primary-buttons'
import { TransactionTypesProvider } from '@/components/admin/transaction-types/transaction-types-provider'
import { TransactionTypesTable } from '@/components/admin/transaction-types/transaction-types-table'
import { index as transactionTypesIndex } from '@/routes/admin/transaction-types'
import { type TransactionType } from '@/types/models/transaction-type'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  transactionTypes: Paginated<TransactionType>
  search: SearchParams
}

export default function TransactionTypesIndex() {
  const { transactionTypes, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Transaction Types" />

      <TransactionTypesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Transaction Types
              </h2>
              <p className="text-muted-foreground">
                Classify ledger movements and manage custom transaction types.
              </p>
            </div>
            <TransactionTypesPrimaryButtons />
          </div>

          <TransactionTypesTable
            transactionTypes={transactionTypes}
            search={search}
          />
        </Main>

        <TransactionTypesDialogs />
      </TransactionTypesProvider>
    </>
  )
}

TransactionTypesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Transaction Types',
      href: transactionTypesIndex.url(),
    },
  ],
}
