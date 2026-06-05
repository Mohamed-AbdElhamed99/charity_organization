import { Shield, UserCheck, Users, Heart } from 'lucide-react'
import type { UserStatus } from '@/types/models/user'

export const callTypes = new Map<UserStatus, string>([
  ['active', 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  ['inactive', 'bg-neutral-300/40 border-neutral-300'],
  ['banned', 'bg-destructive/10 dark:bg-destructive/50 text-destructive dark:text-primary border-destructive/10'],
])

export const roles = [
  {
    label: 'Super Admin',
    value: 'super_admin',
    icon: Shield,
  },
  {
    label: 'Staff',
    value: 'staff',
    icon: UserCheck,
  },
  {
    label: 'Field Worker',
    value: 'field_worker',
    icon: Users,
  },
  {
    label: 'Donor',
    value: 'donor',
    icon: Heart,
  },
] as const

export function roleOptionsFromNames(roleNames: string[]) {
  return roleNames.map((name) => {
    const known = roles.find((role) => role.value === name)

    return {
      label: known?.label ?? name.replace(/_/g, ' '),
      value: name,
      icon: known?.icon,
    }
  })
}
