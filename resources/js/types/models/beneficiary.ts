export type BeneficiaryType = 'individual' | 'family' | 'organization'

export type BeneficiaryStatus =
  | 'pending_assessment'
  | 'active'
  | 'inactive'

export type IndividualSubtype = 'adult' | 'child'

export type AssessmentStatus = 'pending' | 'approved' | 'rejected'

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

export type BeneficiaryListItem = {
  id: number
  code: string
  type: BeneficiaryType
  type_label: string
  status: BeneficiaryStatus
  status_label: string
  display_name: string
  national_id: string | null
  address: string | null
  country_name: string | null
  state_name: string | null
  primary_contact: string | null
  created_at: string | null
  can_view_sensitive: boolean
}

export type BeneficiaryIndividual = {
  id?: number
  subtype: IndividualSubtype
  first_name: string
  middle_name: string | null
  last_name: string
  full_name?: string
  gender: 'male' | 'female' | null
  birthdate: string | null
  national_id: string | null
  phone: string | null
  address: string | null
  country_id: number | null
  state_id: number | null
  country?: { id: number; name: string } | null
  state?: { id: number; name: string } | null
  health_status: string | null
  education_level: string | null
  marital_status: string | null
  employment_status: string | null
  monthly_income: number | null
  date_of_father_death: string | null
  school_year: string | null
  sibling_number: number | null
  behavior_notes: string | null
  notes: string | null
}

export type BeneficiaryFamilyMember = {
  id?: number
  subtype: IndividualSubtype
  first_name: string
  middle_name: string | null
  last_name: string | null
  full_name?: string
  gender: 'male' | 'female' | null
  birthdate: string | null
  national_id: string | null
  relation: string | null
  health_status: string | null
  education_level: string | null
  marital_status: string | null
  employment_status: string | null
  monthly_income: number | null
  date_of_father_death: string | null
  school_year: string | null
  sibling_number: number | null
  behavior_notes: string | null
}

export type BeneficiaryFamily = {
  id?: number
  household_name: string
  national_id: string | null
  phone: string | null
  address: string | null
  village: string | null
  country_id: number | null
  state_id: number | null
  country?: { id: number; name: string } | null
  state?: { id: number; name: string } | null
  social_status: string | null
  total_members: number
  monthly_income: number | null
  housing_type: string | null
  housing_ownership: string | null
  monthly_rent: number | null
  notes: string | null
  members?: BeneficiaryFamilyMember[]
}

export type BeneficiaryOrganization = {
  id?: number
  name: string
  organization_type: string | null
  charity_number: string | null
  phone: string | null
  email: string | null
  address: string | null
  country_id: number | null
  state_id: number | null
  country?: { id: number; name: string } | null
  state?: { id: number; name: string } | null
  contact_person: string | null
  contact_phone: string | null
  notes: string | null
}

export type BeneficiaryAssessment = {
  id: number
  beneficiary_id: number
  assessed_by: number
  assessor?: { id: number; name: string }
  assessment_date: string
  purpose: string | null
  housing_details: Record<string, unknown> | null
  economic_details: Record<string, unknown> | null
  health_details: Record<string, unknown> | null
  family_details: Record<string, unknown> | null
  researcher_opinion: string | null
  recommended_aid_amount: number | null
  status: AssessmentStatus
  status_label: string
  rejection_reason: string | null
  reviewed_by: number | null
  reviewer?: { id: number; name: string } | null
  reviewed_at: string | null
  created_at: string | null
}

export type Beneficiary = {
  id: number
  type: BeneficiaryType
  type_label: string
  code: string
  status: BeneficiaryStatus
  status_label: string
  display_name: string
  primary_contact: string | null
  notes: string | null
  created_by: number
  creator?: { id: number; name: string }
  created_at: string | null
  updated_at: string | null
  can_view_sensitive: boolean
  individual?: BeneficiaryIndividual | null
  family?: BeneficiaryFamily | null
  organization?: BeneficiaryOrganization | null
  assessments?: BeneficiaryAssessment[]
  campaigns_count?: number
}

export type SelectOption = {
  value: string
  label: string
}
