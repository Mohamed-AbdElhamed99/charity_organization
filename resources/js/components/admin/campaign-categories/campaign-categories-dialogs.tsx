import { CampaignCategoriesActionDialog } from './campaign-categories-action-dialog'
import { CampaignCategoriesDeleteDialog } from './campaign-categories-delete-dialog'
import { useCampaignCategories } from './campaign-categories-provider'

export function CampaignCategoriesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useCampaignCategories()

  return (
    <>
      <CampaignCategoriesActionDialog
        key="campaign-category-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <CampaignCategoriesActionDialog
            key={`campaign-category-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <CampaignCategoriesDeleteDialog
            key={`campaign-category-delete-${currentRow.id}`}
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
