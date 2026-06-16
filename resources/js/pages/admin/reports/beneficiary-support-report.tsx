import { Head, router, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type Line = {
  line_id: number
  supported_at: string
  status: string
  item_name_snapshot: string
  quantity: number
  unit_cost: number
  total_cost: number
  campaign_expense_id: number | null
}

type GroupedCampaign = {
  campaign_id: number
  campaign_title_en: string | null
  campaign_title_ar: string | null
  campaign_total_cost: number
  lines: Line[]
}

type PageProps = {
  beneficiary: { id: number; code: string; display_name: string; type: string | null }
  grouped: GroupedCampaign[]
  totals: { grand_total_cost: number; total_items: number; campaigns_count: number }
  filters: Record<string, string | undefined>
}

const formatUsd = (cents: number): string =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100)

export default function BeneficiarySupportReportPage() {
  const { beneficiary, grouped, totals, filters } = usePage<PageProps>().props

  const applyFilter = (patch: Record<string, string | undefined>) => {
    router.get(route('admin.beneficiaries.support-report', beneficiary.id), { ...filters, ...patch }, {
      preserveState: true,
      preserveScroll: true,
    })
  }

  const exportCsv = route('admin.beneficiaries.support-report', {
    beneficiary: beneficiary.id,
    ...filters,
    format: 'csv',
  })
  const exportXlsx = route('admin.beneficiaries.support-report', {
    beneficiary: beneficiary.id,
    ...filters,
    format: 'xlsx',
  })

  return (
    <>
      <Head title="Beneficiary Support Report" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Beneficiary Support Report</h2>
            <p className="text-muted-foreground">
              {beneficiary.display_name} ({beneficiary.code})
            </p>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline" size="sm"><a href={exportCsv}>Export CSV</a></Button>
            <Button asChild variant="outline" size="sm"><a href={exportXlsx}>Export XLSX</a></Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Campaigns</p>
            <p className="text-2xl font-bold">{totals.campaigns_count}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Items</p>
            <p className="text-2xl font-bold">{totals.total_items}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Grand total</p>
            <p className="text-2xl font-bold">{formatUsd(totals.grand_total_cost)}</p>
          </div>
        </div>

        <div className="grid gap-4 rounded-lg border p-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="from">From</Label>
            <Input id="from" type="date" value={filters.from ?? ''} onChange={(event) => applyFilter({ from: event.target.value || undefined })} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="to">To</Label>
            <Input id="to" type="date" value={filters.to ?? ''} onChange={(event) => applyFilter({ to: event.target.value || undefined })} />
          </div>
        </div>

        <div className="space-y-4">
          {grouped.map((campaignGroup) => (
            <div key={campaignGroup.campaign_id} className="rounded-lg border">
              <div className="flex items-center justify-between border-b px-4 py-3">
                <h3 className="font-semibold">
                  {campaignGroup.campaign_title_en ??
                    campaignGroup.campaign_title_ar ??
                    `Campaign #${campaignGroup.campaign_id}`}
                </h3>
                <span className="text-sm font-medium">
                  {formatUsd(campaignGroup.campaign_total_cost)}
                </span>
              </div>
              <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                  <tr>
                    <th className="px-4 py-3">Date</th>
                    <th className="px-4 py-3">Item</th>
                    <th className="px-4 py-3">Qty</th>
                    <th className="px-4 py-3">Unit</th>
                    <th className="px-4 py-3">Total</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {campaignGroup.lines.map((line) => (
                    <tr key={line.line_id} className="border-t">
                      <td className="px-4 py-3">{line.supported_at}</td>
                      <td className="px-4 py-3">{line.item_name_snapshot}</td>
                      <td className="px-4 py-3">{line.quantity}</td>
                      <td className="px-4 py-3">{formatUsd(line.unit_cost)}</td>
                      <td className="px-4 py-3">{formatUsd(line.total_cost)}</td>
                      <td className="px-4 py-3 capitalize">{line.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ))}
        </div>
      </Main>
    </>
  )
}
