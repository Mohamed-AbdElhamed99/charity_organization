import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { AppealForm } from '@/components/admin/campaign-appeals/appeal-form'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { index as campaignsIndex } from '@/routes/admin/campaigns'
import { index as appealsIndex, store } from '@/routes/admin/campaigns/appeals'

type CampaignSummary = {
  id: number
  title_en: string
  title_ar: string
}

type PageProps = {
  campaign: CampaignSummary
}

export default function AppealsCreate() {
  const { campaign } = usePage<PageProps>().props

  return (
    <>
      <Head title={`New appeal — ${campaign.title_en}`} />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={appealsIndex.url(campaign.id)}>
              <ArrowLeft className="me-2 size-4" />
              Back to appeals
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">New appeal</h2>
          <p className="text-muted-foreground">{campaign.title_en}</p>
        </div>

        <AppealForm
          submitUrl={store.url(campaign.id)}
          method="post"
          submitLabel="Save appeal"
          cancelUrl={appealsIndex.url(campaign.id)}
        />
      </Main>
    </>
  )
}

AppealsCreate.layout = {
  breadcrumbs: [
    { title: 'Campaigns', href: campaignsIndex.url() },
    { title: 'New appeal' },
  ],
}
