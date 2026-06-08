import type { TransactionDirection, TransactionType } from '@/types/enums'

export const transactionTypeOptions: {
  label: string
  value: TransactionType
}[] = [
  { label: 'Donation', value: 'donation' },
  { label: 'Campaign Expense', value: 'campaign_expense' },
  { label: 'General Expense', value: 'general_expense' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'Adjustment', value: 'adjustment' },
]

export const transactionDirectionOptions: {
  label: string
  value: TransactionDirection
}[] = [
  { label: 'In', value: 'in' },
  { label: 'Out', value: 'out' },
]

export function formatMoney(
  value: number | string | null | undefined,
  symbol?: string
): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  const numeric = typeof value === 'string' ? Number(value) : value

  if (Number.isNaN(numeric)) {
    return '—'
  }

  const formatted = numeric.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  return symbol ? `${symbol} ${formatted}` : formatted
}
