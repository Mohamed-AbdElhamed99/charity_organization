import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { CampaignsDialogs } from '@/components/admin/campaigns/campaigns-dialogs'
import { CampaignsPrimaryButtons } from '@/components/admin/campaigns/campaigns-primary-buttons'
import { CampaignsProvider } from '@/components/admin/campaigns/campaigns-provider'
import { CampaignsTable } from '@/components/admin/campaigns/campaigns-table'
import { index as campaignsIndex } from '@/routes/admin/campaigns'
import type { Campaign, CampaignCategoryOption } from '@/types/models/campaign'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  category?: string | string[]
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  campaigns: Paginated<Campaign>
  categories: CampaignCategoryOption[]
  search: SearchParams
}

export default function CampaignsIndex() {
  const { campaigns, categories, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Campaigns" />

      <CampaignsProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">Campaigns</h2>
              <p className="text-muted-foreground">
                Manage fundraising campaigns and track progress.
              </p>
            </div>
            <CampaignsPrimaryButtons />
          </div>

          <CampaignsTable
            campaigns={campaigns}
            categories={categories}
            search={search}
          />
        </Main>

        <CampaignsDialogs categories={categories} />
      </CampaignsProvider>
    </>
  )
}

CampaignsIndex.layout = {
  breadcrumbs: [
    {
      title: 'Campaigns',
      href: campaignsIndex.url(),
    },
  ],
}
