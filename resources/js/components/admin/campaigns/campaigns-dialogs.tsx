import { CampaignsActionSheet } from './campaigns-action-sheet'
import { CampaignsDeleteDialog } from './campaigns-delete-dialog'
import { useCampaigns } from './campaigns-provider'
import type { CampaignCategoryOption } from '@/types/models/campaign'

type CampaignsDialogsProps = {
  categories: CampaignCategoryOption[]
}

export function CampaignsDialogs({ categories }: CampaignsDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useCampaigns()

  return (
    <>
      <CampaignsActionSheet
        key="campaign-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        categories={categories}
      />

      {currentRow && (
        <>
          <CampaignsActionSheet
            key={`campaign-edit-${currentRow.id}`}
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

          <CampaignsDeleteDialog
            key={`campaign-delete-${currentRow.id}`}
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
