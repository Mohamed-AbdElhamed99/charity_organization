import { NewsActionSheet } from './news-action-sheet'
import { NewsDeleteDialog } from './news-delete-dialog'
import { useNews } from './news-provider'
import type { NewsCategory } from '@/types/models/news'

type NewsDialogsProps = {
  categories: NewsCategory[]
}

export function NewsDialogs({ categories }: NewsDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useNews()

  return (
    <>
      <NewsActionSheet
        key="news-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        categories={categories}
      />

      {currentRow && (
        <>
          <NewsActionSheet
            key={`news-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
            categories={categories}
          />

          <NewsDeleteDialog
            key={`news-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />
        </>
      )}
    </>
  )
}
