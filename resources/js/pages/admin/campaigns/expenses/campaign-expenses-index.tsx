import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { CampaignExpensesDialogs } from '@/components/admin/campaign-expenses/campaign-expenses-dialogs'
import { CampaignExpensesPrimaryButtons } from '@/components/admin/campaign-expenses/campaign-expenses-primary-buttons'
import { CampaignExpensesProvider } from '@/components/admin/campaign-expenses/campaign-expenses-provider'
import { CampaignExpensesTable } from '@/components/admin/campaign-expenses/campaign-expenses-table'
import { index as campaignsIndex, show as campaignsShow } from '@/routes/admin/campaigns'
import { index as campaignExpensesRoute } from '@/routes/admin/campaigns/expenses'
import type {
  CampaignExpense,
  CampaignExpenseAccountOption,
  CampaignExpenseItemOption,
  CampaignExpenseUserOption,
} from '@/types/models/campaign-expense'
import type { Paginated } from '@/types/pagination'

type CampaignSummary = {
  id: number
  title_en: string
  title_ar: string
}

type SearchParams = {
  query?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

type PageProps = {
  campaign: CampaignSummary
  expenses: Paginated<CampaignExpense>
  items: CampaignExpenseItemOption[]
  accounts: CampaignExpenseAccountOption[]
  users: CampaignExpenseUserOption[]
  search: SearchParams
}

export default function CampaignScopedExpensesIndex() {
  const { campaign, expenses, items, accounts, users, search } =
    usePage<PageProps>().props

  const expensesIndexUrl = campaignExpensesRoute.url(campaign.id)

  return (
    <>
      <Head title={`${campaign.title_en} — Expenses`} />

      <CampaignExpensesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-center gap-4">
            <Button variant="outline" size="sm" asChild>
              <Link href={campaignsShow.url(campaign.id)}>
                <ArrowLeft className="me-2 size-4" />
                Back to campaign
              </Link>
            </Button>
          </div>

          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Campaign Expenses
              </h2>
              <p className="text-muted-foreground">{campaign.title_en}</p>
              <p className="text-sm text-muted-foreground" dir="rtl">
                {campaign.title_ar}
              </p>
            </div>
            <CampaignExpensesPrimaryButtons />
          </div>

          <CampaignExpensesTable
            expenses={expenses}
            search={search}
            indexUrl={expensesIndexUrl}
            showCampaignColumn={false}
          />
        </Main>

        <CampaignExpensesDialogs
          fixedCampaign={campaign}
          items={items}
          accounts={accounts}
          users={users}
        />
      </CampaignExpensesProvider>
    </>
  )
}

CampaignScopedExpensesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Campaigns',
      href: campaignsIndex.url(),
    },
    {
      title: 'Expenses',
    },
  ],
}
