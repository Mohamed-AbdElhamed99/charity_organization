import {
  createContext,
  useContext,
  useState,
  type Dispatch,
  type ReactNode,
  type SetStateAction,
} from 'react'
import type { BeneficiaryListItem } from '@/types/models/beneficiary'

type BeneficiariesDialogType = 'delete'

type BeneficiariesContextProps = {
  open: BeneficiariesDialogType | null
  setOpen: Dispatch<SetStateAction<BeneficiariesDialogType | null>>
  currentRow: BeneficiaryListItem | null
  setCurrentRow: Dispatch<SetStateAction<BeneficiaryListItem | null>>
}

const BeneficiariesContext = createContext<BeneficiariesContextProps | null>(
  null
)

export function BeneficiariesProvider({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState<BeneficiariesDialogType | null>(null)
  const [currentRow, setCurrentRow] = useState<BeneficiaryListItem | null>(
    null
  )

  return (
    <BeneficiariesContext.Provider
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </BeneficiariesContext.Provider>
  )
}

export function useBeneficiaries() {
  const context = useContext(BeneficiariesContext)

  if (!context) {
    throw new Error('useBeneficiaries must be used within BeneficiariesProvider')
  }

  return context
}
