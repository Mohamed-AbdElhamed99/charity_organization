export type DonorType = 'individual' | 'organization'

export type GeoCountry = {
  id: number
  name: string
}

export type GeoState = {
  id: number
  country_id: number
  name: string
}

export type GeoOptions = {
  countries: GeoCountry[]
  states: GeoState[]
}

export type SelectOption = {
  value: string
  label: string
}

export type AvailableDonorUser = {
  id: number
  name: string
  email: string
}

export type DonorProfileListItem = {
  id: number
  user_id: number
  display_name: string
  user_name: string | null
  user_email: string | null
  type: DonorType
  type_label: string
  organization_name: string | null
  country_name: string | null
  state_name: string | null
  created_at: string | null
  deleted_at: string | null
}

export type DonorProfileUser = {
  id: number
  name: string
  email: string
  phone: string | null
  status: string | null
}

export type DonorProfile = {
  id: number
  user_id: number
  display_name: string
  type: DonorType
  type_label: string
  organization_name: string | null
  address: string | null
  country_id: number | null
  country_name: string | null
  state_id: number | null
  state_name: string | null
  notes: string | null
  created_at: string | null
  deleted_at: string | null
  user?: DonorProfileUser
}
