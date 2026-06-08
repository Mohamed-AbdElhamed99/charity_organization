import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Faq } from '@/types/models/faq'

type FaqsDialogType = 'add' | 'edit' | 'delete'

type FaqsContextType = {
  open: FaqsDialogType | null
  setOpen: (str: FaqsDialogType | null) => void
  currentRow: Faq | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Faq | null>>
}

const FaqsContext = React.createContext<FaqsContextType | null>(null)

export function FaqsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<FaqsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Faq | null>(null)

  return (
    <FaqsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </FaqsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useFaqs = () => {
  const context = React.useContext(FaqsContext)

  if (!context) {
    throw new Error('useFaqs has to be used within <FaqsProvider>')
  }

  return context
}
