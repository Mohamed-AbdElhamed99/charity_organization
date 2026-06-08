import { NewsCategoriesActionDialog } from './news-categories-action-dialog'
import { NewsCategoriesDeleteDialog } from './news-categories-delete-dialog'
import { useNewsCategories } from './news-categories-provider'

export function NewsCategoriesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useNewsCategories()

  return (
    <>
      <NewsCategoriesActionDialog
        key="news-category-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <NewsCategoriesActionDialog
            key={`news-category-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <NewsCategoriesDeleteDialog
            key={`news-category-delete-${currentRow.id}`}
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
