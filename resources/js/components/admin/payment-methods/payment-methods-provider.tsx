import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type PaymentMethod } from '@/types/models/payment-method'

type PaymentMethodsDialogType = 'add' | 'edit' | 'delete'

type PaymentMethodsContextType = {
  open: PaymentMethodsDialogType | null
  setOpen: (str: PaymentMethodsDialogType | null) => void
  currentRow: PaymentMethod | null
  setCurrentRow: React.Dispatch<React.SetStateAction<PaymentMethod | null>>
}

const PaymentMethodsContext =
  React.createContext<PaymentMethodsContextType | null>(null)

export function PaymentMethodsProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<PaymentMethodsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<PaymentMethod | null>(null)

  return (
    <PaymentMethodsContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </PaymentMethodsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const usePaymentMethods = () => {
  const context = React.useContext(PaymentMethodsContext)

  if (!context) {
    throw new Error(
      'usePaymentMethods has to be used within <PaymentMethodsProvider>'
    )
  }

  return context
}
