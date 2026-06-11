import type {
  BeneficiaryStatus,
  BeneficiaryType,
  SelectOption,
} from '@/types/models/beneficiary'

export const typeOptions: SelectOption[] = [
  { label: 'Individual', value: 'individual' },
  { label: 'Family / Household', value: 'family' },
  { label: 'Organization', value: 'organization' },
]

export const statusOptions: SelectOption[] = [
  { label: 'Pending Assessment', value: 'pending_assessment' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

export const subtypeOptions: SelectOption[] = [
  { label: 'Adult', value: 'adult' },
  { label: 'Child', value: 'child' },
]

export const genderOptions: SelectOption[] = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
]

export const typeBadgeColors = new Map<BeneficiaryType, string>([
  [
    'individual',
    'bg-sky-100/30 text-sky-900 dark:text-sky-200 border-sky-200',
  ],
  [
    'family',
    'bg-violet-100/30 text-violet-900 dark:text-violet-200 border-violet-200',
  ],
  [
    'organization',
    'bg-orange-100/30 text-orange-900 dark:text-orange-200 border-orange-200',
  ],
])

export const statusBadgeColors = new Map<BeneficiaryStatus, string>([
  [
    'pending_assessment',
    'bg-amber-100/30 text-amber-900 dark:text-amber-200 border-amber-200',
  ],
  ['active', 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [
    'inactive',
    'bg-neutral-300/40 border-neutral-300 text-neutral-700 dark:text-neutral-300',
  ],
])

export const assessmentStatusColors = new Map<string, string>([
  ['pending', 'bg-amber-100/30 text-amber-900 border-amber-200'],
  ['approved', 'bg-teal-100/30 text-teal-900 border-teal-200'],
  ['rejected', 'bg-red-100/30 text-red-900 border-red-200'],
])

export function optionsFromServer(options: SelectOption[]): SelectOption[] {
  return options
}

export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }

  return new Date(value).toLocaleDateString()
}

export function formatAmount(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—'
  }

  return value.toLocaleString(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

export function emptyIndividual() {
  return {
    subtype: 'adult' as const,
    first_name: '',
    middle_name: '',
    last_name: '',
    gender: '',
    birthdate: '',
    national_id: '',
    phone: '',
    address: '',
    country_id: '',
    state_id: '',
    health_status: '',
    education_level: '',
    marital_status: '',
    employment_status: '',
    monthly_income: '',
    date_of_father_death: '',
    school_year: '',
    sibling_number: '',
    behavior_notes: '',
    notes: '',
  }
}

export function emptyFamilyMember() {
  return {
    subtype: 'adult' as const,
    first_name: '',
    middle_name: '',
    last_name: '',
    gender: '',
    birthdate: '',
    national_id: '',
    relation: '',
    health_status: '',
    education_level: '',
    marital_status: '',
    employment_status: '',
    monthly_income: '',
    date_of_father_death: '',
    school_year: '',
    sibling_number: '',
    behavior_notes: '',
  }
}

export function emptyFamily() {
  return {
    household_name: '',
    national_id: '',
    phone: '',
    address: '',
    village: '',
    country_id: '',
    state_id: '',
    social_status: '',
    total_members: '',
    monthly_income: '',
    housing_type: '',
    housing_ownership: '',
    monthly_rent: '',
    notes: '',
    members: [emptyFamilyMember()],
  }
}

export function emptyOrganization() {
  return {
    name: '',
    organization_type: '',
    charity_number: '',
    phone: '',
    email: '',
    address: '',
    country_id: '',
    state_id: '',
    contact_person: '',
    contact_phone: '',
    notes: '',
  }
}
