import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type CampaignCategory } from '@/types/models/campaign-category'

type CampaignCategoriesDialogType = 'add' | 'edit' | 'delete'

type CampaignCategoriesContextType = {
  open: CampaignCategoriesDialogType | null
  setOpen: (str: CampaignCategoriesDialogType | null) => void
  currentRow: CampaignCategory | null
  setCurrentRow: React.Dispatch<React.SetStateAction<CampaignCategory | null>>
}

const CampaignCategoriesContext =
  React.createContext<CampaignCategoriesContextType | null>(null)

export function CampaignCategoriesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<CampaignCategoriesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<CampaignCategory | null>(null)

  return (
    <CampaignCategoriesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </CampaignCategoriesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useCampaignCategories = () => {
  const context = React.useContext(CampaignCategoriesContext)

  if (!context) {
    throw new Error(
      'useCampaignCategories has to be used within <CampaignCategoriesProvider>'
    )
  }

  return context
}
