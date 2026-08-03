import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Currency } from '@/types/models/currency'

type CurrenciesDialogType = 'add' | 'edit' | 'delete'

type CurrenciesContextType = {
  open: CurrenciesDialogType | null
  setOpen: (str: CurrenciesDialogType | null) => void
  currentRow: Currency | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Currency | null>>
}

const CurrenciesContext = React.createContext<CurrenciesContextType | null>(null)

export function CurrenciesProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<CurrenciesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Currency | null>(null)

  return (
    <CurrenciesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </CurrenciesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useCurrencies = () => {
  const context = React.useContext(CurrenciesContext)

  if (!context) {
    throw new Error('useCurrencies has to be used within <CurrenciesProvider>')
  }

  return context
}
