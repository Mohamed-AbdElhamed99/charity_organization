import { GeneralExpenseCategoriesActionDialog } from './general-expense-categories-action-dialog'
import { GeneralExpenseCategoriesDeleteDialog } from './general-expense-categories-delete-dialog'
import { useGeneralExpenseCategories } from './general-expense-categories-provider'

export function GeneralExpenseCategoriesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } =
    useGeneralExpenseCategories()

  return (
    <>
      <GeneralExpenseCategoriesActionDialog
        key="general-expense-category-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <GeneralExpenseCategoriesActionDialog
            key={`general-expense-category-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <GeneralExpenseCategoriesDeleteDialog
            key={`general-expense-category-delete-${currentRow.id}`}
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
