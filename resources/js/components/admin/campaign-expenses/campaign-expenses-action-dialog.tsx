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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import {
  defaultExpenseFormValues,
  selectOptionsFromAccounts,
  selectOptionsFromCampaigns,
  selectOptionsFromItems,
  selectOptionsFromUsers,
} from './data/data'
import type {
  CampaignExpenseAccountOption,
  CampaignExpenseCampaignOption,
  CampaignExpenseItemOption,
  CampaignExpenseUserOption,
} from '@/types/models/campaign-expense'

type FixedCampaign = {
  id: number
  title_en: string
  title_ar: string
}

type CampaignExpensesActionDialogProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  campaigns?: CampaignExpenseCampaignOption[]
  fixedCampaign?: FixedCampaign
  items: CampaignExpenseItemOption[]
  accounts: CampaignExpenseAccountOption[]
  users: CampaignExpenseUserOption[]
}

export function CampaignExpensesActionDialog({
  open,
  onOpenChange,
  campaigns = [],
  fixedCampaign,
  items,
  accounts,
  users,
}: CampaignExpensesActionDialogProps) {
  const form = useForm(defaultExpenseFormValues(fixedCampaign?.id))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(defaultExpenseFormValues(fixedCampaign?.id))
  }, [open, fixedCampaign?.id])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      campaign_id: Number(data.campaign_id),
      account_id: Number(data.account_id),
      item_id: Number(data.item_id),
      item_price: Number(data.item_price),
      quantity: Number(data.quantity),
      responsible_user_id: Number(data.responsible_user_id),
    }))

    form.post(route('admin.campaign-expenses.store'), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    })
  }

  const campaignOptions = selectOptionsFromCampaigns(campaigns)
  const itemOptions = selectOptionsFromItems(items)
  const accountOptions = selectOptionsFromAccounts(accounts)
  const userOptions = selectOptionsFromUsers(users)

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader className="text-start">
          <DialogTitle>Record Campaign Expense</DialogTitle>
          <DialogDescription>
            {fixedCampaign
              ? `Add an expense for ${fixedCampaign.title_en}.`
              : 'Record a new campaign expense.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="campaign-expense-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          {fixedCampaign ? (
            <div className="grid gap-2">
              <Label>Campaign</Label>
              <div className="rounded-md border px-3 py-2 text-sm">
                <div>{fixedCampaign.title_en}</div>
                <div className="text-muted-foreground" dir="rtl">
                  {fixedCampaign.title_ar}
                </div>
              </div>
              <input
                type="hidden"
                name="campaign_id"
                value={form.data.campaign_id}
              />
            </div>
          ) : (
            <div className="grid gap-2">
              <Label htmlFor="campaign_id">Campaign</Label>
              <Select
                value={form.data.campaign_id}
                onValueChange={(value) => form.setData('campaign_id', value)}
              >
                <SelectTrigger id="campaign_id">
                  <SelectValue placeholder="Select campaign" />
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
          )}

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
            <Label htmlFor="item_id">Item</Label>
            <Select
              value={form.data.item_id}
              onValueChange={(value) => form.setData('item_id', value)}
            >
              <SelectTrigger id="item_id">
                <SelectValue placeholder="Select item" />
              </SelectTrigger>
              <SelectContent>
                {itemOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    <span>{option.label}</span>
                    <span className="ms-2 text-muted-foreground" dir="rtl">
                      {option.labelAr}
                    </span>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.item_id} />
          </div>

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="item_price">Item price</Label>
              <Input
                id="item_price"
                type="number"
                min="0"
                step="0.01"
                value={form.data.item_price}
                onChange={(event) =>
                  form.setData('item_price', event.target.value)
                }
                required
              />
              <InputError message={form.errors.item_price} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="quantity">Quantity</Label>
              <Input
                id="quantity"
                type="number"
                min="0.001"
                step="0.001"
                value={form.data.quantity}
                onChange={(event) =>
                  form.setData('quantity', event.target.value)
                }
                required
              />
              <InputError message={form.errors.quantity} />
            </div>
          </div>

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="expense_date">Expense date</Label>
              <Input
                id="expense_date"
                type="date"
                value={form.data.expense_date}
                onChange={(event) =>
                  form.setData('expense_date', event.target.value)
                }
                required
              />
              <InputError message={form.errors.expense_date} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="responsible_user_id">Responsible user</Label>
              <Select
                value={form.data.responsible_user_id}
                onValueChange={(value) =>
                  form.setData('responsible_user_id', value)
                }
              >
                <SelectTrigger id="responsible_user_id">
                  <SelectValue placeholder="Select user" />
                </SelectTrigger>
                <SelectContent>
                  {userOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.responsible_user_id} />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="description">Description</Label>
            <Input
              id="description"
              value={form.data.description}
              onChange={(event) =>
                form.setData('description', event.target.value)
              }
            />
            <InputError message={form.errors.description} />
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

        <DialogFooter>
          <Button
            type="submit"
            form="campaign-expense-form"
            disabled={form.processing}
          >
            Save expense
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
