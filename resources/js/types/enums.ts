export type CampaignStatus = 'draft' | 'active' | 'completed' | 'cancelled'

export type CampaignRecurrence = 'never' | 'daily' | 'weekly' | 'monthly'

export type TransactionType =
  | 'donation'
  | 'campaign_expense'
  | 'general_expense'
  | 'transfer'
  | 'bank_transfer'
  | 'adjustment'

export type TransactionDirection = 'in' | 'out'

export type TransferRecipientType = 'vendor' | 'beneficiary' | 'user' | 'other'
