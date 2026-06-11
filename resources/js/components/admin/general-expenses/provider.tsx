import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import type { GeneralExpense } from '@/types/models/general-expense'

type GeneralExpensesDialogType = 'add' | 'edit' | 'reverse'

type GeneralExpensesContextType = {
  open: GeneralExpensesDialogType | null
  setOpen: (str: GeneralExpensesDialogType | null) => void
  currentRow: GeneralExpense | null
  setCurrentRow: React.Dispatch<React.SetStateAction<GeneralExpense | null>>
}

const GeneralExpensesContext =
  React.createContext<GeneralExpensesContextType | null>(null)

export function GeneralExpensesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<GeneralExpensesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<GeneralExpense | null>(null)

  return (
    <GeneralExpensesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </GeneralExpensesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useGeneralExpenses = () => {
  const context = React.useContext(GeneralExpensesContext)

  if (!context) {
    throw new Error(
      'useGeneralExpenses has to be used within <GeneralExpensesProvider>'
    )
  }

  return context
}
