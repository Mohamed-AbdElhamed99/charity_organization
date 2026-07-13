import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { CampaignForm } from '@/components/admin/campaigns/campaign-form'
import type { MeetingSelectOption } from '@/components/admin/campaigns/meeting-multi-select'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import {
  create,
  index as campaignsIndex,
  store,
} from '@/routes/admin/campaigns'
import type { CampaignCategoryOption } from '@/types/models/campaign'

type PageProps = {
  categories: CampaignCategoryOption[]
  meetingOptions: MeetingSelectOption[]
}

export default function CampaignsCreate() {
  const { categories, meetingOptions } = usePage<PageProps>().props

  return (
    <>
      <Head title="New Campaign" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={campaignsIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to campaigns
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">New campaign</h2>
          <p className="text-muted-foreground">
            Create a fundraising campaign and link related meetings.
          </p>
        </div>

        <CampaignForm
          categories={categories}
          meetingOptions={meetingOptions}
          submitUrl={store.url()}
          method="post"
          submitLabel="Save & view"
          cancelUrl={campaignsIndex.url()}
        />
      </Main>
    </>
  )
}

CampaignsCreate.layout = {
  breadcrumbs: [
    { title: 'Campaigns', href: campaignsIndex.url() },
    { title: 'Create', href: create.url() },
  ],
}
