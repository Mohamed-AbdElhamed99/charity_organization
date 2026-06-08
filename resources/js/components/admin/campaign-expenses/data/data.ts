export function formatExpenseAmount(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—'
  }

  return value.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export function selectOptionsFromCampaigns(
  campaigns: { id: number; title_en: string; title_ar: string }[]
) {
  return campaigns.map((campaign) => ({
    label: campaign.title_en,
    value: String(campaign.id),
  }))
}

export function selectOptionsFromItems(
  items: { id: number; name_en: string; name_ar: string }[]
) {
  return items.map((item) => ({
    label: item.name_en,
    labelAr: item.name_ar,
    value: String(item.id),
  }))
}

export function selectOptionsFromAccounts(accounts: { id: number; name: string }[]) {
  return accounts.map((account) => ({
    label: account.name,
    value: String(account.id),
  }))
}

export function selectOptionsFromUsers(users: { id: number; name: string }[]) {
  return users.map((user) => ({
    label: user.name,
    value: String(user.id),
  }))
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

export function defaultExpenseFormValues(campaignId?: number) {
  return {
    campaign_id: campaignId != null ? String(campaignId) : '',
    account_id: '',
    item_id: '',
    item_price: '',
    quantity: '1',
    expense_date: todayDateInputValue(),
    responsible_user_id: '',
    description: '',
    notes: '',
  }
}
