import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import InputError from '@/components/input-error'
import { RolesPermissionsSection } from './roles-permissions-section'
import { type PermissionGroups, type Role } from '@/types/models/role'

type RolesActionSheetProps = {
  currentRow?: Role
  open: boolean
  onOpenChange: (open: boolean) => void
  permissionGroups: PermissionGroups
}

export function RolesActionSheet({
  currentRow,
  open,
  onOpenChange,
  permissionGroups,
}: RolesActionSheetProps) {
  const isEdit = !!currentRow
  const isProtected = currentRow?.is_protected ?? false

  const form = useForm({
    name: currentRow?.name ?? '',
    permissions: currentRow?.permissions ?? ([] as string[]),
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      name: currentRow?.name ?? '',
      permissions: currentRow?.permissions ?? [],
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.roles.update', currentRow.id), options)
      return
    }

    form.post(route('admin.roles.store'), options)
  }

  return (
    <Sheet
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <SheetContent className="flex w-full flex-col sm:max-w-xl">
        <SheetHeader className="text-start">
          <SheetTitle>{isEdit ? 'Edit Role' : 'Add Role'}</SheetTitle>
          <SheetDescription>
            {isProtected
              ? 'This system role cannot be modified.'
              : isEdit
                ? 'Update role name and assigned permissions.'
                : 'Create a role and assign permissions.'}
          </SheetDescription>
        </SheetHeader>

        <form
          id="role-form"
          onSubmit={handleSubmit}
          className="flex flex-1 flex-col gap-4 overflow-y-auto py-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="name">Role name</Label>
            <Input
              id="name"
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              disabled={isProtected}
              required
            />
            <InputError message={form.errors.name} />
          </div>

          <RolesPermissionsSection
            permissionGroups={permissionGroups}
            selected={form.data.permissions}
            onChange={(permissions) => form.setData('permissions', permissions)}
            error={form.errors.permissions}
            disabled={isProtected}
          />
        </form>

        <SheetFooter>
          <Button
            type="submit"
            form="role-form"
            disabled={form.processing || isProtected}
          >
            Save changes
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
