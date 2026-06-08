import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import {
  accountOptionsFromList,
  campaignOptionsFromList,
  recipientTypeOptions,
} from './data/data'
import type { CampaignOption } from '@/types/models/transfer'
import type { AccountOption } from '@/types/models/transaction'

type TransfersActionSheetProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  campaigns: CampaignOption[]
  accounts: AccountOption[]
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

export function TransfersActionSheet({
  open,
  onOpenChange,
  campaigns,
  accounts,
}: TransfersActionSheetProps) {
  const form = useForm({
    account_id: '',
    campaign_id: '',
    recipient_type: 'vendor',
    recipient_name: '',
    recipient_phone: '',
    amount: '',
    transfer_date: todayDateInputValue(),
    purpose: '',
    notes: '',
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      account_id: '',
      campaign_id: '',
      recipient_type: 'vendor',
      recipient_name: '',
      recipient_phone: '',
      amount: '',
      transfer_date: todayDateInputValue(),
      purpose: '',
      notes: '',
    })
  }, [open])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      account_id: data.account_id ? Number(data.account_id) : null,
      campaign_id: data.campaign_id ? Number(data.campaign_id) : null,
    }))

    form.post(route('admin.transfers.store'), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  const accountOptions = accountOptionsFromList(accounts)
  const campaignOptions = campaignOptionsFromList(campaigns)

  return (
    <Sheet
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <SheetContent className="flex w-full flex-col sm:max-w-xl">
        <SheetHeader className="text-start">
          <SheetTitle>Record Transfer</SheetTitle>
          <SheetDescription>
            Record an outgoing transfer to a vendor, beneficiary, or staff member.
          </SheetDescription>
        </SheetHeader>

        <form
          id="transfer-form"
          onSubmit={handleSubmit}
          className="flex flex-1 flex-col gap-4 overflow-y-auto py-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="account_id">Account</Label>
            <Select
              value={form.data.account_id}
              onValueChange={(value) => form.setData('account_id', value)}
            >
              <SelectTrigger id="account_id">
                <SelectValue placeholder="Select account" />
              </SelectTrigger>
              <SelectContent>
                {accountOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.account_id} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="campaign_id">Campaign (optional)</Label>
            <Select
              value={form.data.campaign_id || undefined}
              onValueChange={(value) => form.setData('campaign_id', value)}
            >
              <SelectTrigger id="campaign_id">
                <SelectValue placeholder="No campaign" />
              </SelectTrigger>
              <SelectContent>
                {campaignOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.campaign_id} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="recipient_type">Recipient type</Label>
            <Select
              value={form.data.recipient_type}
              onValueChange={(value) => form.setData('recipient_type', value)}
            >
              <SelectTrigger id="recipient_type">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {recipientTypeOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.recipient_type} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="recipient_name">Recipient name</Label>
            <Input
              id="recipient_name"
              value={form.data.recipient_name}
              onChange={(event) =>
                form.setData('recipient_name', event.target.value)
              }
              required
            />
            <InputError message={form.errors.recipient_name} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="recipient_phone">Recipient phone</Label>
            <Input
              id="recipient_phone"
              value={form.data.recipient_phone}
              onChange={(event) =>
                form.setData('recipient_phone', event.target.value)
              }
            />
            <InputError message={form.errors.recipient_phone} />
          </div>

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="amount">Amount</Label>
              <Input
                id="amount"
                type="number"
                min="0.01"
                step="0.01"
                value={form.data.amount}
                onChange={(event) => form.setData('amount', event.target.value)}
                required
              />
              <InputError message={form.errors.amount} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="transfer_date">Transfer date</Label>
              <Input
                id="transfer_date"
                type="date"
                value={form.data.transfer_date}
                onChange={(event) =>
                  form.setData('transfer_date', event.target.value)
                }
                required
              />
              <InputError message={form.errors.transfer_date} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="purpose">Purpose</Label>
            <Input
              id="purpose"
              value={form.data.purpose}
              onChange={(event) => form.setData('purpose', event.target.value)}
              required
            />
            <InputError message={form.errors.purpose} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="notes">Notes</Label>
            <Textarea
              id="notes"
              value={form.data.notes}
              onChange={(event) => form.setData('notes', event.target.value)}
              rows={3}
            />
            <InputError message={form.errors.notes} />
          </div>
        </form>

        <SheetFooter>
          <Button type="submit" form="transfer-form" disabled={form.processing}>
            Save transfer
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
