import { type Faq } from '@/types/models/faq'

export const statusOptions = [
  { label: 'Published', value: 'published' },
  { label: 'Draft', value: 'draft' },
] as const

export const callTypes = new Map<boolean, string>([
  [true, 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [false, 'bg-neutral-300/40 border-neutral-300'],
])

export function getFaqDisplayQuestion(faq: Faq): string {
  return faq.question_en || faq.question_ar
}
