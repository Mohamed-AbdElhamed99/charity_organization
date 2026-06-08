export interface CampaignExpense {
  id: number
  transaction_id: number | null
  campaign_id: number
  campaign_name: string | null
  item_id: number
  item_name: string | null
  item_price: number
  quantity: number
  amount: number
  residual_quantity: number
  residual_amount: number
  responsible_user_id: number
  responsible_user_name: string | null
  expense_date: string
  notes: string | null
  created_at: string
}

export interface CampaignExpenseCampaignOption {
  id: number
  title_ar: string
  title_en: string
}

export interface CampaignExpenseItemOption {
  id: number
  name_ar: string
  name_en: string
}

export interface CampaignExpenseAccountOption {
  id: number
  name: string
}

export interface CampaignExpenseUserOption {
  id: number
  name: string
}
