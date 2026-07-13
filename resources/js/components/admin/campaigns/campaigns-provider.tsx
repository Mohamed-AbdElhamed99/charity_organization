import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Campaign } from '@/types/models/campaign'

type CampaignsDialogType = 'delete'

type CampaignsContextType = {
  open: CampaignsDialogType | null
  setOpen: (str: CampaignsDialogType | null) => void
  currentRow: Campaign | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Campaign | null>>
}

const CampaignsContext = React.createContext<CampaignsContextType | null>(null)

export function CampaignsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<CampaignsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Campaign | null>(null)

  return (
    <CampaignsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </CampaignsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useCampaigns = () => {
  const context = React.useContext(CampaignsContext)

  if (!context) {
    throw new Error('useCampaigns has to be used within <CampaignsProvider>')
  }

  return context
}
