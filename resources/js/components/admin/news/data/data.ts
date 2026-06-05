import type { NewsStatus } from '@/types/models/news'

export const statusTypes = new Map<NewsStatus, string>([
  ['active', 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  ['inactive', 'bg-neutral-300/40 border-neutral-300'],
  ['published', 'bg-blue-100/30 text-blue-900 dark:text-blue-200 border-blue-200'],
  ['draft', 'bg-amber-100/30 text-amber-900 dark:text-amber-200 border-amber-200'],
])

export const statusOptions: { label: string; value: NewsStatus }[] = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Published', value: 'published' },
  { label: 'Draft', value: 'draft' },
]

export function categoryOptionsFromList(
  categories: { id: number; name_en: string }[]
) {
  return categories.map((category) => ({
    label: category.name_en,
    value: String(category.id),
  }))
}

export function getNewsDisplayStatus(news: {
  is_active: boolean
  published_at: string | null
}): NewsStatus {
  if (
    news.is_active &&
    news.published_at &&
    new Date(news.published_at) <= new Date()
  ) {
    return 'published'
  }

  if (!news.is_active || !news.published_at) {
    return 'draft'
  }

  return news.is_active ? 'active' : 'inactive'
}
