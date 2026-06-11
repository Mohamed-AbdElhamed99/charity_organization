import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'

type GeneralExpenseCategoriesDialogType = 'add' | 'edit' | 'delete'

type GeneralExpenseCategoriesContextType = {
  open: GeneralExpenseCategoriesDialogType | null
  setOpen: (str: GeneralExpenseCategoriesDialogType | null) => void
  currentRow: GeneralExpenseCategory | null
  setCurrentRow: React.Dispatch<
    React.SetStateAction<GeneralExpenseCategory | null>
  >
}

const GeneralExpenseCategoriesContext =
  React.createContext<GeneralExpenseCategoriesContextType | null>(null)

export function GeneralExpenseCategoriesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] =
    useDialogState<GeneralExpenseCategoriesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<GeneralExpenseCategory | null>(
    null
  )

  return (
    <GeneralExpenseCategoriesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </GeneralExpenseCategoriesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useGeneralExpenseCategories = () => {
  const context = React.useContext(GeneralExpenseCategoriesContext)

  if (!context) {
    throw new Error(
      'useGeneralExpenseCategories has to be used within <GeneralExpenseCategoriesProvider>'
    )
  }

  return context
}
