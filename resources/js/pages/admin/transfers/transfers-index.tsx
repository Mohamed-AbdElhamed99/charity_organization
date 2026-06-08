import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { TransfersDialogs } from '@/components/admin/transfers/transfers-dialogs'
import { TransfersPrimaryButtons } from '@/components/admin/transfers/transfers-primary-buttons'
import { TransfersProvider } from '@/components/admin/transfers/transfers-provider'
import { TransfersTable } from '@/components/admin/transfers/transfers-table'
import { index as transfersIndex } from '@/routes/admin/transfers'
import type { CampaignOption, Transfer } from '@/types/models/transfer'
import type { AccountOption } from '@/types/models/transaction'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  recipient_type?: string | string[]
  campaign_id?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

type PageProps = {
  transfers: Paginated<Transfer>
  campaigns: CampaignOption[]
  accounts: AccountOption[]
  search: SearchParams
}

export default function TransfersIndex() {
  const { transfers, campaigns, accounts, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Transfers" />

      <TransfersProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">Transfers</h2>
              <p className="text-muted-foreground">
                Record and review outgoing transfers.
              </p>
            </div>
            <TransfersPrimaryButtons />
          </div>

          <TransfersTable
            transfers={transfers}
            campaigns={campaigns}
            search={search}
          />
        </Main>

        <TransfersDialogs campaigns={campaigns} accounts={accounts} />
      </TransfersProvider>
    </>
  )
}

TransfersIndex.layout = {
  breadcrumbs: [
    {
      title: 'Transfers',
      href: transfersIndex.url(),
    },
  ],
}
