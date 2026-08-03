import type { TransactionDirection } from '@/types/enums'

export type TransactionTypeCode =
  | 'donation'
  | 'campaign_expense'
  | 'general_expense'
  | 'transfer'
  | 'bank_transfer'
  | 'adjustment'

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
