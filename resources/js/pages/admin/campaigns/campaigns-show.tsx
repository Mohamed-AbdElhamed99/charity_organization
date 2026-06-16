import { Head, Link, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { formatAmount, statusTypes } from '@/components/admin/campaigns/data/data'
import { index as campaignsIndex } from '@/routes/admin/campaigns'
import type { Campaign } from '@/types/models/campaign'

type PageProps = {
  campaign: Campaign
}

export default function CampaignsShow() {
  const { campaign } = usePage<PageProps>().props
  const badgeColor = statusTypes.get(campaign.status)

  return (
    <>
      <Head title={campaign.title_en} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={campaignsIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to campaigns
            </Link>
          </Button>
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.campaigns.beneficiary-supports.create', campaign.id)}>
              Record support
            </Link>
          </Button>
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.campaigns.beneficiary-report', campaign.id)}>
              Beneficiary report
            </Link>
          </Button>
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.campaigns.support-reconciliation', campaign.id)}>
              Reconciliation
            </Link>
          </Button>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            {campaign.cover_url && (
              <img
                src={campaign.cover_url}
                alt={campaign.title_en}
                className="aspect-video w-full rounded-lg border object-cover"
              />
            )}

            <div className="space-y-2">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-2xl font-bold tracking-tight">
                  {campaign.title_en}
                </h2>
                <Badge variant="outline" className={cn('capitalize', badgeColor)}>
                  {campaign.status}
                </Badge>
              </div>
              {campaign.category_name && (
                <Badge variant="secondary">{campaign.category_name}</Badge>
              )}
              <p className="text-sm text-muted-foreground">{campaign.slug}</p>
            </div>

            {campaign.excerpt_en && (
              <p className="text-muted-foreground">{campaign.excerpt_en}</p>
            )}

            {campaign.description_en && (
              <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap">
                {campaign.description_en}
              </div>
            )}

            <div className="space-y-4 rounded-lg border p-4" dir="rtl">
              <h3 className="text-lg font-semibold">{campaign.title_ar}</h3>
              {campaign.excerpt_ar && (
                <p className="text-muted-foreground">{campaign.excerpt_ar}</p>
              )}
              {campaign.description_ar && (
                <div className="whitespace-pre-wrap">{campaign.description_ar}</div>
              )}
            </div>

            {campaign.gallery.length > 0 && (
              <div className="space-y-3">
                <h3 className="text-lg font-semibold">Gallery</h3>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                  {campaign.gallery.map((item) => (
                    <div key={item.id} className="overflow-hidden rounded-md border">
                      {item.mime_type.startsWith('video/') ? (
                        <video
                          src={item.url}
                          controls
                          className="aspect-video w-full object-cover"
                        />
                      ) : (
                        <img
                          src={item.url}
                          alt="Gallery item"
                          className="aspect-video w-full object-cover"
                        />
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Financial Summary</CardTitle>
                <CardDescription>Campaign budget and progress</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Budget</span>
                  <span className="font-medium">
                    {formatAmount(campaign.budget)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Donation Target</span>
                  <span className="font-medium">
                    {formatAmount(campaign.donation_target)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Total Donated</span>
                  <span className="font-medium">
                    {formatAmount(campaign.total_donated)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Total Expenses</span>
                  <span className="font-medium">
                    {formatAmount(campaign.total_expenses)}
                  </span>
                </div>
                <div className="flex justify-between border-t pt-3">
                  <span className="text-muted-foreground">Remaining Budget</span>
                  <span className="font-semibold">
                    {formatAmount(campaign.remaining_budget)}
                  </span>
                </div>
                {campaign.donation_progress != null && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Donation Progress</span>
                    <span className="font-medium">
                      {campaign.donation_progress.toFixed(1)}%
                    </span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Expenses Count</span>
                  <span className="font-medium">
                    {campaign.expenses_count ?? 0}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Donations Count</span>
                  <span className="font-medium">
                    {campaign.donations_count ?? 0}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Start Date</span>
                  <span>{campaign.start_date ?? '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">End Date</span>
                  <span>{campaign.end_date ?? '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Public</span>
                  <span>{campaign.is_public ? 'Yes' : 'No'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Donation Form</span>
                  <span>{campaign.open_donation_form ? 'Open' : 'Closed'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Recurrence</span>
                  <span className="capitalize">{campaign.is_repeated}</span>
                </div>
                {campaign.repeat_until && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Repeat Until</span>
                    <span>{campaign.repeat_until}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Created</span>
                  <span>{campaign.created_at}</span>
                </div>
              </CardContent>
            </Card>

            {(campaign.meta_title_en ||
              campaign.meta_title_ar ||
              campaign.meta_description_en ||
              campaign.meta_description_ar) && (
              <Card>
                <CardHeader>
                  <CardTitle>SEO</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  {campaign.meta_title_en && (
                    <div>
                      <p className="text-muted-foreground">Meta Title (EN)</p>
                      <p>{campaign.meta_title_en}</p>
                    </div>
                  )}
                  {campaign.meta_title_ar && (
                    <div dir="rtl">
                      <p className="text-muted-foreground">Meta Title (AR)</p>
                      <p>{campaign.meta_title_ar}</p>
                    </div>
                  )}
                  {campaign.meta_description_en && (
                    <div>
                      <p className="text-muted-foreground">
                        Meta Description (EN)
                      </p>
                      <p>{campaign.meta_description_en}</p>
                    </div>
                  )}
                  {campaign.meta_description_ar && (
                    <div dir="rtl">
                      <p className="text-muted-foreground">
                        Meta Description (AR)
                      </p>
                      <p>{campaign.meta_description_ar}</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </Main>
    </>
  )
}

CampaignsShow.layout = {
  breadcrumbs: [
    {
      title: 'Campaigns',
      href: campaignsIndex.url(),
    },
    {
      title: 'Details',
    },
  ],
}
