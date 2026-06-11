import type {
  AvailableDonorUser,
  GeoOptions,
  SelectOption,
} from '@/types/models/donor-profile'
import { DonorProfilesActionDialog } from './donor-profiles-action-dialog'
import { DonorProfilesDeleteDialog } from './donor-profiles-delete-dialog'
import { useDonorProfiles } from './donor-profiles-provider'

type DonorProfilesDialogsProps = {
  availableUsers: AvailableDonorUser[]
  geoOptions: GeoOptions
  typeOptions: SelectOption[]
}

export function DonorProfilesDialogs({
  availableUsers,
  geoOptions,
  typeOptions,
}: DonorProfilesDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useDonorProfiles()

  return (
    <>
      <DonorProfilesActionDialog
        key="donor-profile-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        availableUsers={availableUsers}
        geoOptions={geoOptions}
        typeOptions={typeOptions}
      />

      {currentRow && (
        <DonorProfilesDeleteDialog
          key={`donor-profile-delete-${currentRow.id}`}
          open={open === 'delete'}
          onOpenChange={() => {
            setOpen('delete')
            setTimeout(() => {
              setCurrentRow(null)
            }, 500)
          }}
          currentRow={currentRow}
        />
      )}
    </>
  )
}
