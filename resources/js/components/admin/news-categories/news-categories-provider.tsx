import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type NewsCategory } from '@/types/models/news-category'

type NewsCategoriesDialogType = 'add' | 'edit' | 'delete'

type NewsCategoriesContextType = {
  open: NewsCategoriesDialogType | null
  setOpen: (str: NewsCategoriesDialogType | null) => void
  currentRow: NewsCategory | null
  setCurrentRow: React.Dispatch<React.SetStateAction<NewsCategory | null>>
}

const NewsCategoriesContext = React.createContext<NewsCategoriesContextType | null>(null)

export function NewsCategoriesProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<NewsCategoriesDialogType>(null)
  const [currentRow, setCurrentRow] = useState<NewsCategory | null>(null)

  return (
    <NewsCategoriesContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </NewsCategoriesContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useNewsCategories = () => {
  const context = React.useContext(NewsCategoriesContext)

  if (!context) {
    throw new Error('useNewsCategories has to be used within <NewsCategoriesProvider>')
  }

  return context
}
