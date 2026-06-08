import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { AccountsDialogs } from '@/components/admin/accounts/accounts-dialogs'
import { AccountsPrimaryButtons } from '@/components/admin/accounts/accounts-primary-buttons'
import { AccountsProvider } from '@/components/admin/accounts/accounts-provider'
import { AccountsTable } from '@/components/admin/accounts/accounts-table'
import { index as accountsIndex } from '@/routes/admin/accounts'
import { type Account, type AccountTypeOption, type CurrencyOption } from '@/types/models/account'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  type?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  accounts: Paginated<Account>
  currencies: CurrencyOption[]
  accountTypes: AccountTypeOption[]
  search: SearchParams
}

export default function AccountsIndex() {
  const { accounts, currencies, accountTypes, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Accounts" />

      <AccountsProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">Accounts</h2>
              <p className="text-muted-foreground">
                Manage financial accounts used for transactions and transfers.
              </p>
            </div>
            <AccountsPrimaryButtons />
          </div>

          <AccountsTable
            accounts={accounts}
            search={search}
            currencies={currencies}
            accountTypes={accountTypes}
          />
        </Main>

        <AccountsDialogs currencies={currencies} accountTypes={accountTypes} />
      </AccountsProvider>
    </>
  )
}

AccountsIndex.layout = {
  breadcrumbs: [
    {
      title: 'Accounts',
      href: accountsIndex.url(),
    },
  ],
}
