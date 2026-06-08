import { RolesActionSheet } from './roles-action-sheet'
import { RolesDeleteDialog } from './roles-delete-dialog'
import { useRoles } from './roles-provider'
import { type PermissionGroups } from '@/types/models/role'

type RolesDialogsProps = {
  permissionGroups: PermissionGroups
}

export function RolesDialogs({ permissionGroups }: RolesDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useRoles()

  return (
    <>
      <RolesActionSheet
        key="role-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        permissionGroups={permissionGroups}
      />

      {currentRow && (
        <>
          <RolesActionSheet
            key={`role-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
            permissionGroups={permissionGroups}
          />

          <RolesDeleteDialog
            key={`role-delete-${currentRow.id}`}
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
