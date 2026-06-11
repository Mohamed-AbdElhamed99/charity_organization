import type {
  DonorProfile,
  DonorProfileListItem,
  DonorType,
  SelectOption,
} from '@/types/models/donor-profile'

export const typeBadgeColors = new Map<DonorType, string>([
  [
    'individual',
    'bg-sky-100/30 text-sky-900 dark:text-sky-200 border-sky-200',
  ],
  [
    'organization',
    'bg-orange-100/30 text-orange-900 dark:text-orange-200 border-orange-200',
  ],
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

export function getDonorDisplayName(
  profile: DonorProfile | DonorProfileListItem
): string {
  return profile.display_name || profile.user_name || `Donor #${profile.id}`
}
