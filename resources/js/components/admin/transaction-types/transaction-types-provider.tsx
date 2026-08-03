import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type TransactionType } from '@/types/models/transaction-type'

type TransactionTypesDialogType = 'add' | 'edit' | 'delete'

type TransactionTypesContextType = {
  open: TransactionTypesDialogType | null
  setOpen: (str: TransactionTypesDialogType | null) => void
  currentRow: TransactionType | null
  setCurrentRow: React.Dispatch<React.SetStateAction<TransactionType | null>>
}

const TransactionTypesContext =
  React.createContext<TransactionTypesContextType | null>(null)

export function TransactionTypesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<TransactionTypesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<TransactionType | null>(null)

  return (
    <TransactionTypesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </TransactionTypesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useTransactionTypes = () => {
  const context = React.useContext(TransactionTypesContext)

  if (!context) {
    throw new Error(
      'useTransactionTypes has to be used within <TransactionTypesProvider>'
    )
  }

  return context
}
