export interface Role {
  id: number
  name: string
  guard_name: string
  permissions: string[]
  users_count?: number
  is_system: boolean
  is_protected: boolean
}

export type PermissionGroups = Record<string, string[]>
