import { FaqsActionDialog } from './faqs-action-dialog'
import { FaqsDeleteDialog } from './faqs-delete-dialog'
import { useFaqs } from './faqs-provider'

export function FaqsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useFaqs()

  return (
    <>
      <FaqsActionDialog
        key="faq-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <FaqsActionDialog
            key={`faq-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <FaqsDeleteDialog
            key={`faq-delete-${currentRow.id}`}
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
