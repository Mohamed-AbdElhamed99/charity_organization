import { Head, router, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type ReportRow = {
  beneficiary_id: number
  beneficiary_code: string
  beneficiary_name: string
  beneficiary_type: string
  support_events_count: number
  items_count: number
  total_cost: number
  last_supported_at: string | null
}

type Summary = {
  distinct_beneficiaries: number
  support_events: number
  total_items: number
  total_cost: number
}

type PageProps = {
  campaign: { id: number; title_en: string | null; title_ar: string | null }
  rows: {
    data: ReportRow[]
    current_page: number
    last_page: number
  }
  summary: Summary
  filters: Record<string, string | undefined>
}

const formatUsd = (cents: number): string =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(cents / 100)

export default function CampaignBeneficiaryReportPage() {
  const { campaign, rows, summary, filters } = usePage<PageProps>().props

  const setFilter = (patch: Record<string, string | undefined>) => {
    router.get(route('admin.campaigns.beneficiary-report', campaign.id), { ...filters, ...patch }, {
      preserveState: true,
      preserveScroll: true,
    })
  }

  const exportCsv = route('admin.campaigns.beneficiary-report', {
    campaign: campaign.id,
    ...filters,
    format: 'csv',
  })

  const exportXlsx = route('admin.campaigns.beneficiary-report', {
    campaign: campaign.id,
    ...filters,
    format: 'xlsx',
  })

  return (
    <>
      <Head title="Campaign Beneficiary Report" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Campaign Beneficiary Report</h2>
            <p className="text-muted-foreground">
              {campaign.title_en ?? campaign.title_ar ?? `Campaign #${campaign.id}`}
            </p>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline" size="sm"><a href={exportCsv}>Export CSV</a></Button>
            <Button asChild variant="outline" size="sm"><a href={exportXlsx}>Export XLSX</a></Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-4">
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Beneficiaries</p>
            <p className="text-2xl font-bold">{summary.distinct_beneficiaries}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Support events</p>
            <p className="text-2xl font-bold">{summary.support_events}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Items</p>
            <p className="text-2xl font-bold">{summary.total_items}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Total cost</p>
            <p className="text-2xl font-bold">{formatUsd(summary.total_cost)}</p>
          </div>
        </div>

        <div className="grid gap-4 rounded-lg border p-4 md:grid-cols-4">
          <div className="space-y-2">
            <Label htmlFor="from">From</Label>
            <Input id="from" type="date" value={filters.from ?? ''} onChange={(event) => setFilter({ from: event.target.value || undefined })} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="to">To</Label>
            <Input id="to" type="date" value={filters.to ?? ''} onChange={(event) => setFilter({ to: event.target.value || undefined })} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="query">Search</Label>
            <Input id="query" value={filters.query ?? ''} onChange={(event) => setFilter({ query: event.target.value || undefined })} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="status">Status</Label>
            <select
              id="status"
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={filters.status ?? ''}
              onChange={(event) => setFilter({ status: event.target.value || undefined })}
            >
              <option value="">All</option>
              <option value="planned">Planned</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <div className="overflow-hidden rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left">
              <tr>
                <th className="px-4 py-3">Beneficiary</th>
                <th className="px-4 py-3">Type</th>
                <th className="px-4 py-3">Events</th>
                <th className="px-4 py-3">Items</th>
                <th className="px-4 py-3">Total</th>
                <th className="px-4 py-3">Last support</th>
              </tr>
            </thead>
            <tbody>
              {rows.data.map((row) => (
                <tr key={row.beneficiary_id} className="border-t">
                  <td className="px-4 py-3">
                    <div className="font-medium">{row.beneficiary_name}</div>
                    <div className="text-xs text-muted-foreground">{row.beneficiary_code}</div>
                  </td>
                  <td className="px-4 py-3 capitalize">{row.beneficiary_type}</td>
                  <td className="px-4 py-3">{row.support_events_count}</td>
                  <td className="px-4 py-3">{row.items_count}</td>
                  <td className="px-4 py-3">{formatUsd(row.total_cost)}</td>
                  <td className="px-4 py-3">{row.last_supported_at ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Main>
    </>
  )
}
