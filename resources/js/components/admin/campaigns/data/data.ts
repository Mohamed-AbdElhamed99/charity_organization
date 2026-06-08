import type { CampaignRecurrence, CampaignStatus } from '@/types/enums'
import type { Campaign, CampaignCategoryOption } from '@/types/models/campaign'

export const statusOptions: { label: string; value: CampaignStatus }[] = [
  { label: 'Draft', value: 'draft' },
  { label: 'Active', value: 'active' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

export const statusTypes = new Map<CampaignStatus, string>([
  ['draft', 'bg-amber-100/30 text-amber-900 dark:text-amber-200 border-amber-200'],
  ['active', 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  ['completed', 'bg-blue-100/30 text-blue-900 dark:text-blue-200 border-blue-200'],
  ['cancelled', 'bg-neutral-300/40 border-neutral-300'],
])

export const recurrenceOptions: { label: string; value: CampaignRecurrence }[] = [
  { label: 'Never', value: 'never' },
  { label: 'Daily', value: 'daily' },
  { label: 'Weekly', value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
]

export function categoryOptionsFromList(categories: CampaignCategoryOption[]) {
  return categories.map((category) => ({
    label: category.name_en,
    value: String(category.id),
  }))
}

export function getCampaignDisplayName(campaign: Campaign): string {
  return campaign.title_en || campaign.title_ar
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
