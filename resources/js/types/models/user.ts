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

export interface UserShow {
  id: number
  name: string
  email: string
  phone: string | null
  status: UserStatus
  national_id: string | null
  job: string | null
  birthdate: string | null
  bio: string | null
  gender: 'male' | 'female' | null
  address: string | null
  country_name: string | null
  state_name: string | null
  avatar: string
  created_at: string
  email_verified_at: string | null
  roles: string[]
  permissions: string[]
  deleted_at: string | null
}
