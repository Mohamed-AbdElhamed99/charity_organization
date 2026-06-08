import { type NewsCategory } from '@/types/models/news-category'

export const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
] as const

export const callTypes = new Map<boolean, string>([
  [true, 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [false, 'bg-neutral-300/40 border-neutral-300'],
])

export function getCategoryDisplayName(category: NewsCategory, locale?: string): string {
  if (locale === 'ar') {
    return category.name_ar || category.name_en
  }

  return category.name_en || category.name_ar
}
