import { CampaignExpensesActionDialog } from './campaign-expenses-action-dialog'
import { useCampaignExpenses } from './campaign-expenses-provider'
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

type CampaignExpensesDialogsProps = {
  campaigns?: CampaignExpenseCampaignOption[]
  fixedCampaign?: FixedCampaign
  items: CampaignExpenseItemOption[]
  accounts: CampaignExpenseAccountOption[]
  users: CampaignExpenseUserOption[]
}

export function CampaignExpensesDialogs({
  campaigns,
  fixedCampaign,
  items,
  accounts,
  users,
}: CampaignExpensesDialogsProps) {
  const { open, setOpen } = useCampaignExpenses()

  return (
    <CampaignExpensesActionDialog
      key={
        fixedCampaign
          ? `campaign-expense-add-${fixedCampaign.id}`
          : 'campaign-expense-add'
      }
      open={open === 'add'}
      onOpenChange={() => setOpen('add')}
      campaigns={campaigns}
      fixedCampaign={fixedCampaign}
      items={items}
      accounts={accounts}
      users={users}
    />
  )
}
