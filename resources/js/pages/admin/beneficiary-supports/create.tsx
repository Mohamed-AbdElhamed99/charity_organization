import { FormEvent, useMemo } from 'react'
import { Head, useForm, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type BeneficiaryOption = {
  id: number
  code: string
  display_name: string
  type: string | null
}

type CampaignOption = {
  id: number
  title_ar: string | null
  title_en: string | null
}

type AidItemOption = {
  id: number
  name: { en?: string; ar?: string }
  unit: { en?: string; ar?: string } | null
  default_unit_cost: number | null
}

type CampaignExpenseOption = {
  id: number
  campaign_id: number
  expense_date: string
  amount: string | number
}

type PageProps = {
  mode: 'campaign' | 'beneficiary'
  campaign: CampaignOption | null
  beneficiary: BeneficiaryOption | null
  beneficiaries: BeneficiaryOption[]
  campaigns: CampaignOption[]
  aidItems: AidItemOption[]
  campaignExpenses: CampaignExpenseOption[]
}

type SupportLine = {
  aid_item_id: number | null
  item_name_snapshot: string
  quantity: number
  unit_cost: number
  campaign_expense_id: number | null
}

function toUsd(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cents / 100)
}

export default function BeneficiarySupportCreate() {
  const { mode, campaign, beneficiary, beneficiaries, campaigns, aidItems, campaignExpenses } =
    usePage<PageProps>().props

  const { data, setData, post, processing } = useForm({
    beneficiary_id: beneficiary?.id ?? beneficiaries[0]?.id ?? 0,
    campaign_id: campaign?.id ?? campaigns[0]?.id ?? 0,
    supported_at: new Date().toISOString().slice(0, 10),
    status: 'delivered',
    notes: '',
    items: [
      {
        aid_item_id: null,
        item_name_snapshot: '',
        quantity: 1,
        unit_cost: 0,
        campaign_expense_id: null,
      },
    ] as SupportLine[],
  })

  const filteredCampaignExpenses = useMemo(
    () => campaignExpenses.filter((expense) => expense.campaign_id === Number(data.campaign_id)),
    [campaignExpenses, data.campaign_id]
  )

  const setLine = (index: number, next: SupportLine) => {
    const lines = [...data.items]
    lines[index] = next
    setData('items', lines)
  }

  const addLine = () => {
    setData('items', [
      ...data.items,
      {
        aid_item_id: null,
        item_name_snapshot: '',
        quantity: 1,
        unit_cost: 0,
        campaign_expense_id: null,
      },
    ])
  }

  const removeLine = (index: number) => {
    const lines = data.items.filter((_, itemIndex) => itemIndex !== index)
    setData('items', lines.length > 0 ? lines : data.items)
  }

  const totalCost = data.items.reduce(
    (total, item) => total + Number(item.quantity || 0) * Number(item.unit_cost || 0),
    0
  )

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    post(route('admin.beneficiary-supports.store'))
  }

  return (
    <>
      <Head title="Record Beneficiary Support" />
      <Main className="flex flex-1 flex-col gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Record Beneficiary Support</h2>
          <p className="text-muted-foreground">
            Capture operational support distribution without creating ledger entries.
          </p>
        </div>

        <form onSubmit={submit} className="space-y-6">
          <div className="grid gap-4 rounded-lg border p-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="beneficiary">Beneficiary</Label>
              <select
                id="beneficiary"
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={data.beneficiary_id}
                onChange={(event) => setData('beneficiary_id', Number(event.target.value))}
                disabled={mode === 'beneficiary'}
              >
                {beneficiaries.map((option) => (
                  <option key={option.id} value={option.id}>
                    {option.display_name} ({option.code})
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="campaign">Campaign</Label>
              <select
                id="campaign"
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={data.campaign_id}
                onChange={(event) => setData('campaign_id', Number(event.target.value))}
                disabled={mode === 'campaign'}
              >
                {campaigns.map((option) => (
                  <option key={option.id} value={option.id}>
                    {option.title_en ?? option.title_ar ?? `Campaign #${option.id}`}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="supported-at">Supported At</Label>
              <Input
                id="supported-at"
                type="date"
                value={data.supported_at}
                onChange={(event) => setData('supported_at', event.target.value)}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="status">Status</Label>
              <select
                id="status"
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                value={data.status}
                onChange={(event) => setData('status', event.target.value)}
              >
                <option value="planned">Planned</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div className="space-y-3 rounded-lg border p-4">
            <div className="flex items-center justify-between">
              <h3 className="font-semibold">Support Items</h3>
              <Button type="button" variant="outline" size="sm" onClick={addLine}>
                Add line
              </Button>
            </div>

            {data.items.map((line, index) => (
              <div key={index} className="grid gap-3 rounded-md border p-3 md:grid-cols-6">
                <div className="space-y-2 md:col-span-2">
                  <Label>Aid Item</Label>
                  <select
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    value={line.aid_item_id ?? ''}
                    onChange={(event) => {
                      const selectedId = event.target.value ? Number(event.target.value) : null
                      const selectedItem = aidItems.find((item) => item.id === selectedId)
                      setLine(index, {
                        ...line,
                        aid_item_id: selectedId,
                        item_name_snapshot:
                          selectedItem?.name?.en ??
                          selectedItem?.name?.ar ??
                          line.item_name_snapshot,
                        unit_cost: selectedItem?.default_unit_cost ?? line.unit_cost,
                      })
                    }}
                  >
                    <option value="">Ad-hoc item</option>
                    {aidItems.map((item) => (
                      <option key={item.id} value={item.id}>
                        {item.name.en ?? item.name.ar ?? `Item #${item.id}`}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label>Item name snapshot</Label>
                  <Input
                    value={line.item_name_snapshot}
                    onChange={(event) =>
                      setLine(index, { ...line, item_name_snapshot: event.target.value })
                    }
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label>Qty</Label>
                  <Input
                    type="number"
                    min={1}
                    value={line.quantity}
                    onChange={(event) =>
                      setLine(index, { ...line, quantity: Number(event.target.value) || 1 })
                    }
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label>Unit cost (cents)</Label>
                  <Input
                    type="number"
                    min={0}
                    value={line.unit_cost}
                    onChange={(event) =>
                      setLine(index, { ...line, unit_cost: Number(event.target.value) || 0 })
                    }
                    required
                  />
                </div>

                <div className="space-y-2 md:col-span-4">
                  <Label>Campaign expense (optional)</Label>
                  <select
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    value={line.campaign_expense_id ?? ''}
                    onChange={(event) =>
                      setLine(index, {
                        ...line,
                        campaign_expense_id: event.target.value
                          ? Number(event.target.value)
                          : null,
                      })
                    }
                  >
                    <option value="">Not linked</option>
                    {filteredCampaignExpenses.map((expense) => (
                      <option key={expense.id} value={expense.id}>
                        Expense #{expense.id} - {expense.expense_date}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="flex items-end justify-between md:col-span-2">
                  <span className="text-sm text-muted-foreground">
                    Line total: {toUsd(Number(line.quantity || 0) * Number(line.unit_cost || 0))}
                  </span>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => removeLine(index)}
                    disabled={data.items.length <= 1}
                  >
                    Remove
                  </Button>
                </div>
              </div>
            ))}
          </div>

          <div className="rounded-lg border p-4">
            <p className="text-sm text-muted-foreground">Grand total</p>
            <p className="text-2xl font-bold">{toUsd(totalCost)}</p>
          </div>

          <Button type="submit" disabled={processing}>
            Save support record
          </Button>
        </form>
      </Main>
    </>
  )
}
