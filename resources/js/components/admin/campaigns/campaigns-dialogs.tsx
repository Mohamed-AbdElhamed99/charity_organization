import { CampaignsDeleteDialog } from './campaigns-delete-dialog'
import { useCampaigns } from './campaigns-provider'

export function CampaignsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useCampaigns()

  if (!currentRow) {
    return null
  }

  return (
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
  )
}
