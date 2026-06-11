import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'

export const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
] as const

export const callTypes = new Map<boolean, string>([
  [true, 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [false, 'bg-neutral-300/40 border-neutral-300'],
])

export function getGeneralExpenseCategoryDisplayName(
  category: GeneralExpenseCategory
): string {
  return category.name
}
