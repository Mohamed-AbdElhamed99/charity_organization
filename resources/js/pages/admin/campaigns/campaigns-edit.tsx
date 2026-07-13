import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { CampaignForm } from '@/components/admin/campaigns/campaign-form'
import type { MeetingSelectOption } from '@/components/admin/campaigns/meeting-multi-select'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import {
  index as campaignsIndex,
  show,
  update,
} from '@/routes/admin/campaigns'
import type {
  Campaign,
  CampaignCategoryOption,
} from '@/types/models/campaign'

type PageProps = {
  campaign: Campaign
  categories: CampaignCategoryOption[]
  meetingOptions: MeetingSelectOption[]
}

export default function CampaignsEdit() {
  const { campaign, categories, meetingOptions } = usePage<PageProps>().props

  return (
    <>
      <Head title={`Edit ${campaign.title_en}`} />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={show.url(campaign.id)}>
              <ArrowLeft className="me-2 size-4" />
              Back to campaign
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">Edit campaign</h2>
          <p className="text-muted-foreground">{campaign.title_en}</p>
        </div>

        <CampaignForm
          campaign={campaign}
          categories={categories}
          meetingOptions={meetingOptions}
          submitUrl={update.url(campaign.id)}
          method="patch"
          submitLabel="Save changes"
          cancelUrl={show.url(campaign.id)}
        />
      </Main>
    </>
  )
}

CampaignsEdit.layout = {
  breadcrumbs: [
    { title: 'Campaigns', href: campaignsIndex.url() },
    { title: 'Edit', href: '#' },
  ],
}
