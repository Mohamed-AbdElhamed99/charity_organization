import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import type { DonorProfileListItem } from '@/types/models/donor-profile'

type DonorProfilesDialogType = 'add' | 'delete'

type DonorProfilesContextType = {
  open: DonorProfilesDialogType | null
  setOpen: (value: DonorProfilesDialogType | null) => void
  currentRow: DonorProfileListItem | null
  setCurrentRow: React.Dispatch<
    React.SetStateAction<DonorProfileListItem | null>
  >
}

const DonorProfilesContext =
  React.createContext<DonorProfilesContextType | null>(null)

export function DonorProfilesProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const [open, setOpen] = useDialogState<DonorProfilesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<DonorProfileListItem | null>(
    null
  )

  return (
    <DonorProfilesContext
      value={{ open, setOpen, currentRow, setCurrentRow }}
    >
      {children}
    </DonorProfilesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useDonorProfiles = () => {
  const context = React.useContext(DonorProfilesContext)

  if (!context) {
    throw new Error(
      'useDonorProfiles has to be used within <DonorProfilesProvider>'
    )
  }

  return context
}
