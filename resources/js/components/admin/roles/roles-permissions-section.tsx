import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import InputError from '@/components/input-error'
import { type PermissionGroups } from '@/types/models/role'

type RolesPermissionsSectionProps = {
  permissionGroups: PermissionGroups
  selected: string[]
  onChange: (permissions: string[]) => void
  error?: string
  disabled?: boolean
}

function formatPermissionLabel(permission: string): string {
  return permission.replace(/_/g, ' ')
}

export function RolesPermissionsSection({
  permissionGroups,
  selected,
  onChange,
  error,
  disabled = false,
}: RolesPermissionsSectionProps) {
  const togglePermission = (permission: string, checked: boolean) => {
    if (checked) {
      onChange([...selected, permission])
      return
    }

    onChange(selected.filter((item) => item !== permission))
  }

  const toggleGroup = (permissions: string[], checked: boolean) => {
    if (checked) {
      onChange([...new Set([...selected, ...permissions])])
      return
    }

    onChange(selected.filter((item) => !permissions.includes(item)))
  }

  return (
    <div className="space-y-4">
      {Object.entries(permissionGroups).map(([group, permissions]) => {
        const allSelected = permissions.every((permission) =>
          selected.includes(permission)
        )
        const someSelected =
          !allSelected &&
          permissions.some((permission) => selected.includes(permission))

        return (
          <div key={group} className="rounded-md border p-4">
            <div className="mb-3 flex items-center gap-2">
              <Checkbox
                id={`group-${group}`}
                checked={allSelected ? true : someSelected ? 'indeterminate' : false}
                disabled={disabled}
                onCheckedChange={(checked) =>
                  toggleGroup(permissions, checked === true)
                }
              />
              <Label htmlFor={`group-${group}`} className="font-semibold">
                {group}
              </Label>
            </div>

            <div className="grid gap-2 sm:grid-cols-2">
              {permissions.map((permission) => (
                <div key={permission} className="flex items-center gap-2">
                  <Checkbox
                    id={permission}
                    checked={selected.includes(permission)}
                    disabled={disabled}
                    onCheckedChange={(checked) =>
                      togglePermission(permission, checked === true)
                    }
                  />
                  <Label
                    htmlFor={permission}
                    className="text-sm font-normal capitalize"
                  >
                    {formatPermissionLabel(permission)}
                  </Label>
                </div>
              ))}
            </div>
          </div>
        )
      })}

      <InputError message={error} />
    </div>
  )
}
