import type { TransferRecipientType } from '@/types/enums'

export const recipientTypeOptions: {
  label: string
  value: TransferRecipientType
}[] = [
  { label: 'Vendor / Supplier', value: 'vendor' },
  { label: 'Beneficiary', value: 'beneficiary' },
  { label: 'Staff Reimbursement', value: 'user' },
  { label: 'Other', value: 'other' },
]

export function formatTransferAmount(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  const numeric = typeof value === 'string' ? Number(value) : value

  if (Number.isNaN(numeric)) {
    return '—'
  }

  return numeric.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export function campaignOptionsFromList(
  campaigns: { id: number; title_en: string }[]
) {
  return campaigns.map((campaign) => ({
    label: campaign.title_en,
    value: String(campaign.id),
  }))
}

export function accountOptionsFromList(accounts: { id: number; name: string }[]) {
  return accounts.map((account) => ({
    label: account.name,
    value: String(account.id),
  }))
}
