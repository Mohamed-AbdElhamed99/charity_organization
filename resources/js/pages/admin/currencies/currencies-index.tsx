import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { CurrenciesDialogs } from '@/components/admin/currencies/currencies-dialogs'
import { CurrenciesPrimaryButtons } from '@/components/admin/currencies/currencies-primary-buttons'
import { CurrenciesProvider } from '@/components/admin/currencies/currencies-provider'
import { CurrenciesTable } from '@/components/admin/currencies/currencies-table'
import { index as currenciesIndex } from '@/routes/admin/currencies'
import { type Currency } from '@/types/models/currency'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  currencies: Paginated<Currency>
  search: SearchParams
}

export default function CurrenciesIndex() {
  const { currencies, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Currencies" />

      <CurrenciesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">Currencies</h2>
              <p className="text-muted-foreground">
                Manage ledger currencies and FX equivalent options.
              </p>
            </div>
            <CurrenciesPrimaryButtons />
          </div>

          <CurrenciesTable currencies={currencies} search={search} />
        </Main>

        <CurrenciesDialogs />
      </CurrenciesProvider>
    </>
  )
}

CurrenciesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Currencies',
      href: currenciesIndex.url(),
    },
  ],
}
