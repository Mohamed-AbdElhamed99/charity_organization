import { TransfersActionSheet } from './transfers-action-sheet'
import { useTransfers } from './transfers-provider'
import type { CampaignOption } from '@/types/models/transfer'
import type { AccountOption } from '@/types/models/transaction'

type TransfersDialogsProps = {
  campaigns: CampaignOption[]
  accounts: AccountOption[]
}

export function TransfersDialogs({ campaigns, accounts }: TransfersDialogsProps) {
  const { open, setOpen } = useTransfers()

  return (
    <TransfersActionSheet
      key="transfer-add"
      open={open === 'add'}
      onOpenChange={() => setOpen('add')}
      campaigns={campaigns}
      accounts={accounts}
    />
  )
}
