export type UserStatus = 'active' | 'inactive' | 'banned'

export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  status: UserStatus
  role: string | null
  avatar: string
  created_at: string
  email_verified_at: string | null
  deleted_at?: string | null
}
