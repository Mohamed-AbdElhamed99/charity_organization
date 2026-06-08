import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import type { CampaignExpense } from '@/types/models/campaign-expense'

type CampaignExpensesDialogType = 'add'

type CampaignExpensesContextType = {
  open: CampaignExpensesDialogType | null
  setOpen: (str: CampaignExpensesDialogType | null) => void
  currentRow: CampaignExpense | null
  setCurrentRow: React.Dispatch<React.SetStateAction<CampaignExpense | null>>
}

const CampaignExpensesContext =
  React.createContext<CampaignExpensesContextType | null>(null)

export function CampaignExpensesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<CampaignExpensesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<CampaignExpense | null>(null)

  return (
    <CampaignExpensesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </CampaignExpensesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useCampaignExpenses = () => {
  const context = React.useContext(CampaignExpensesContext)

  if (!context) {
    throw new Error(
      'useCampaignExpenses has to be used within <CampaignExpensesProvider>'
    )
  }

  return context
}
