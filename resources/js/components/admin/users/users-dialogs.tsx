import { UsersActionDialog } from './users-action-dialog'
import { UsersDeleteDialog } from './users-delete-dialog'
import { UsersInviteDialog } from './users-invite-dialog'
import { useUsers } from './users-provider'

type UsersDialogsProps = {
  roles: string[]
}

export function UsersDialogs({ roles }: UsersDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useUsers()

  return (
    <>
      <UsersActionDialog
        key="user-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        roles={roles}
      />

      <UsersInviteDialog
        key="user-invite"
        open={open === 'invite'}
        onOpenChange={() => setOpen('invite')}
        roles={roles}
      />

      {currentRow && (
        <>
          <UsersActionDialog
            key={`user-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
            roles={roles}
          />

          <UsersDeleteDialog
            key={`user-delete-${currentRow.id}`}
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
