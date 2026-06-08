import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { CampaignCategoriesDialogs } from '@/components/admin/campaign-categories/campaign-categories-dialogs'
import { CampaignCategoriesPrimaryButtons } from '@/components/admin/campaign-categories/campaign-categories-primary-buttons'
import { CampaignCategoriesProvider } from '@/components/admin/campaign-categories/campaign-categories-provider'
import { CampaignCategoriesTable } from '@/components/admin/campaign-categories/campaign-categories-table'
import { index as campaignCategoriesIndex } from '@/routes/admin/campaign-categories'
import { type CampaignCategory } from '@/types/models/campaign-category'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  campaignCategories: Paginated<CampaignCategory>
  search: SearchParams
}

export default function CampaignCategoriesIndex() {
  const { campaignCategories, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Campaign Categories" />

      <CampaignCategoriesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Campaign Categories
              </h2>
              <p className="text-muted-foreground">
                Manage categories for campaigns.
              </p>
            </div>
            <CampaignCategoriesPrimaryButtons />
          </div>

          <CampaignCategoriesTable
            campaignCategories={campaignCategories}
            search={search}
          />
        </Main>

        <CampaignCategoriesDialogs />
      </CampaignCategoriesProvider>
    </>
  )
}

CampaignCategoriesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Campaign Categories',
      href: campaignCategoriesIndex.url(),
    },
  ],
}
