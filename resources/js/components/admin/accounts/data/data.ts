import { type Account } from '@/types/models/account'

export const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
] as const

export const accountTypeOptions = [
  { label: 'Bank Account', value: 'bank' },
  { label: 'Cash / Petty Cash', value: 'cash' },
  { label: 'Digital Wallet', value: 'digital' },
] as const

export const statusBadgeColors = new Map<boolean, string>([
  [true, 'bg-teal-100/30 text-teal-900 dark:text-teal-200 border-teal-200'],
  [false, 'bg-neutral-300/40 border-neutral-300'],
])

export const typeBadgeColors = new Map<Account['type'], string>([
  ['bank', 'bg-blue-100/30 text-blue-900 dark:text-blue-200 border-blue-200'],
  ['cash', 'bg-green-100/30 text-green-900 dark:text-green-200 border-green-200'],
  ['digital', 'bg-purple-100/30 text-purple-900 dark:text-purple-200 border-purple-200'],
])

export function getAccountDisplayName(account: Account): string {
  return account.name
}
