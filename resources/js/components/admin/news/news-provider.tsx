import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import type { News } from '@/types/models/news'

type NewsDialogType = 'add' | 'edit' | 'delete'

type NewsContextType = {
  open: NewsDialogType | null
  setOpen: (str: NewsDialogType | null) => void
  currentRow: News | null
  setCurrentRow: React.Dispatch<React.SetStateAction<News | null>>
}

const NewsContext = React.createContext<NewsContextType | null>(null)

export function NewsProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = useDialogState<NewsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<News | null>(null)

  return (
    <NewsContext value={{ open, setOpen, currentRow, setCurrentRow }}>
      {children}
    </NewsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useNews = () => {
  const newsContext = React.useContext(NewsContext)

  if (!newsContext) {
    throw new Error('useNews has to be used within <NewsProvider>')
  }

  return newsContext
}
