import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'

type PageProps = {
  campaign: { id: number; title_en: string | null; title_ar: string | null }
  totals: {
    distributed_total: number
    campaign_expenses_total: number
    gap: number
  }
}

const formatUsd = (cents: number): string =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100)

export default function CampaignSupportReconciliationPage() {
  const { campaign, totals } = usePage<PageProps>().props

  return (
    <>
      <Head title="Campaign Support Reconciliation" />
      <Main className="flex flex-1 flex-col gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            Campaign Support Reconciliation
          </h2>
          <p className="text-muted-foreground">
            {campaign.title_en ?? campaign.title_ar ?? `Campaign #${campaign.id}`}
          </p>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Distributed total</p>
            <p className="text-2xl font-bold">{formatUsd(totals.distributed_total)}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Campaign expenses total</p>
            <p className="text-2xl font-bold">{formatUsd(totals.campaign_expenses_total)}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Gap</p>
            <p className="text-2xl font-bold">{formatUsd(totals.gap)}</p>
          </div>
        </div>
      </Main>
    </>
  )
}
