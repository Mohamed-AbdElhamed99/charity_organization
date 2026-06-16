import { Head, router, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Download } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { DataTablePagination } from '@/components/data-table'
import type { Paginated } from '@/types/pagination'

type DonationRow = {
  id: number
  created_at: string
  donor_name: string
  donor_display_name: string
  donor_email: string | null
  is_anonymous: boolean
  purpose: string
  amount: string
  amount_cents: number
  gross_amount: string | null
  fee_amount: string | null
  net_amount: string | null
  currency: string | null
  status: string
  donor_covers_fee: boolean
  stripe_payment_intent_id: string | null
}

type Summary = {
  donation_count: number
  total_gift_cents: number
  total_gross_cents: number
  total_fee_cents: number
  total_net_cents: number
}

type SearchParams = Record<string, string | undefined>

type PageProps = {
  donations: Paginated<DonationRow>
  summary: Summary
  campaigns: Array<{ id: number; title_en: string; title_ar: string }>
  search: SearchParams
}

function formatUsd(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cents / 100)
}

export default function DonationsIndex() {
  const { donations, summary, search } = usePage<PageProps>().props

  const applyFilters = (next: SearchParams) => {
    router.get(route('admin.donations.index'), { ...search, ...next }, {
      preserveState: true,
      preserveScroll: true,
    })
  }

  const exportUrl = route('admin.donations.export', { ...search, format: 'csv' })

  return (
    <>
      <Head title="Donations" />

      <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
        <div className="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h2 className="text-2xl font-bold tracking-tight">Donations</h2>
            <p className="text-muted-foreground">
              Online and recorded gifts with filters and export.
            </p>
          </div>
          <Button asChild variant="outline" size="sm">
            <a href={exportUrl}>
              <Download className="mr-2 h-4 w-4" />
              Export CSV
            </a>
          </Button>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Count</p>
            <p className="text-2xl font-bold">{summary.donation_count}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Total gifts</p>
            <p className="text-2xl font-bold">{formatUsd(summary.total_gift_cents)}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Total charged</p>
            <p className="text-2xl font-bold">{formatUsd(summary.total_gross_cents)}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Total fees</p>
            <p className="text-2xl font-bold">{formatUsd(summary.total_fee_cents)}</p>
          </div>
          <div className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">Total net</p>
            <p className="text-2xl font-bold">{formatUsd(summary.total_net_cents)}</p>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-4">
          <div className="space-y-2">
            <Label htmlFor="from">From</Label>
            <Input
              id="from"
              type="date"
              value={search.from ?? ''}
              onChange={(e) => applyFilters({ from: e.target.value || undefined })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="to">To</Label>
            <Input
              id="to"
              type="date"
              value={search.to ?? ''}
              onChange={(e) => applyFilters({ to: e.target.value || undefined })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="donor">Donor</Label>
            <Input
              id="donor"
              value={search.donor ?? ''}
              onChange={(e) => applyFilters({ donor: e.target.value || undefined })}
              placeholder="Name or email"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="status">Status</Label>
            <select
              id="status"
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              value={search.status ?? ''}
              onChange={(e) => applyFilters({ status: e.target.value || undefined })}
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="succeeded">Succeeded</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>
        </div>

        <div className="overflow-hidden rounded-lg border">
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left">
              <tr>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">Donor</th>
                <th className="px-4 py-3">Purpose</th>
                <th className="px-4 py-3">Gift</th>
                <th className="px-4 py-3">Gross</th>
                <th className="px-4 py-3">Fee</th>
                <th className="px-4 py-3">Net</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Intent</th>
              </tr>
            </thead>
            <tbody>
              {donations.data.map((row) => (
                <tr key={row.id} className="border-t">
                  <td className="px-4 py-3">{row.created_at?.slice(0, 10)}</td>
                  <td className="px-4 py-3">
                    <div className="font-medium">{row.donor_name}</div>
                    {row.is_anonymous && (
                      <span className="text-xs text-muted-foreground">(anonymous)</span>
                    )}
                    <div className="text-xs text-muted-foreground">{row.donor_email}</div>
                  </td>
                  <td className="px-4 py-3">{row.purpose}</td>
                  <td className="px-4 py-3">{row.amount}</td>
                  <td className="px-4 py-3">{row.gross_amount ?? '—'}</td>
                  <td className="px-4 py-3">{row.fee_amount ?? '—'}</td>
                  <td className="px-4 py-3">{row.net_amount ?? '—'}</td>
                  <td className="px-4 py-3">{row.status}</td>
                  <td className="px-4 py-3 font-mono text-xs">
                    {row.stripe_payment_intent_id ?? '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <DataTablePagination
          pagination={donations}
          indexUrl={route('admin.donations.index')}
          search={search}
        />
      </Main>
    </>
  )
}
