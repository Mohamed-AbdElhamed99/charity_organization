import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { CampaignExpensesDialogs } from '@/components/admin/campaign-expenses/campaign-expenses-dialogs'
import { CampaignExpensesPrimaryButtons } from '@/components/admin/campaign-expenses/campaign-expenses-primary-buttons'
import { CampaignExpensesProvider } from '@/components/admin/campaign-expenses/campaign-expenses-provider'
import { CampaignExpensesTable } from '@/components/admin/campaign-expenses/campaign-expenses-table'
import { index as campaignExpensesIndex } from '@/routes/admin/campaign-expenses'
import type {
  CampaignExpense,
  CampaignExpenseAccountOption,
  CampaignExpenseCampaignOption,
  CampaignExpenseItemOption,
  CampaignExpenseUserOption,
} from '@/types/models/campaign-expense'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

type PageProps = {
  expenses: Paginated<CampaignExpense>
  campaigns: CampaignExpenseCampaignOption[]
  items: CampaignExpenseItemOption[]
  accounts: CampaignExpenseAccountOption[]
  users: CampaignExpenseUserOption[]
  search: SearchParams
}

export default function CampaignExpensesIndex() {
  const { expenses, campaigns, items, accounts, users, search } =
    usePage<PageProps>().props

  return (
    <>
      <Head title="Campaign Expenses" />

      <CampaignExpensesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Campaign Expenses
              </h2>
              <p className="text-muted-foreground">
                View and record expenses across all campaigns.
              </p>
            </div>
            <CampaignExpensesPrimaryButtons />
          </div>

          <CampaignExpensesTable
            expenses={expenses}
            search={search}
            indexUrl={campaignExpensesIndex.url()}
          />
        </Main>

        <CampaignExpensesDialogs
          campaigns={campaigns}
          items={items}
          accounts={accounts}
          users={users}
        />
      </CampaignExpensesProvider>
    </>
  )
}

CampaignExpensesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Campaign Expenses',
      href: campaignExpensesIndex.url(),
    },
  ],
}
