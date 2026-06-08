import React from 'react'
import useDialogState from '@/hooks/use-dialog-state'

type TransfersDialogType = 'add'

type TransfersContextType = {
  open: TransfersDialogType | null
  setOpen: (str: TransfersDialogType | null) => void
}

const TransfersContext = React.createContext<TransfersContextType | null>(null)

export function TransfersProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<TransfersDialogType>(null)

  return (
    <TransfersContext value={{ open, setOpen }}>{children}</TransfersContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useTransfers = () => {
  const context = React.useContext(TransfersContext)

  if (!context) {
    throw new Error('useTransfers has to be used within <TransfersProvider>')
  }

  return context
}
