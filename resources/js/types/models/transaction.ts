import type { TransactionDirection, TransactionType } from '@/types/enums'

export interface TransactionAccount {
  id: number
  name: string
  currency_id?: number
}

export interface TransactionCurrency {
  id: number
  code: string
  symbol: string
}

export interface TransactionPaymentMethod {
  id: number
  name: string
  code: string
}

export interface TransactionCreator {
  id: number
  name: string
}

export interface TransactionDocument {
  id: number
  name: string
  mime_type: string
  size: number
  url: string
}

export interface TransactionDonation {
  id: number
  campaign_id: number | null
  donor_name: string | null
  amount: number | string
}

export interface TransactionCampaignExpense {
  id: number
  campaign_id: number
  amount: number | string
  expense_date: string | null
}

export interface TransactionGeneralExpense {
  id: number
  amount: number | string
  expense_date: string | null
}

export interface TransactionTransfer {
  id: number
  campaign_id: number | null
  campaign?: { id: number; title_en: string; title_ar: string } | null
  recipient_kind: 'user' | 'beneficiary' | 'other'
  recipient_id: number | null
  recipient_label: string | null
  recipient_phone: string | null
  recipient_name: string | null
  amount: number | string
  purpose: string | null
  transfer_date: string | null
  notes: string | null
}

export interface TransactionBankExpense {
  id: number
  amount: number | string
  expense_date: string | null
}

export interface Transaction {
  id: number
  account_id: number
  account?: TransactionAccount
  transaction_type: TransactionType | null
  transaction_type_label: string | null
  direction: TransactionDirection | null
  currency_id: number
  currency?: TransactionCurrency
  original_currency_id?: number | null
  original_currency?: TransactionCurrency | null
  gross_amount: number | string
  fee_amount: number | string
  net_amount: number | string
  original_amount?: number | string | null
  exchange_rate?: number | string | null
  running_balance: number | string | null
  transaction_date: string | null
  reference_number: string | null
  description: string | null
  notes: string | null
  payment_method_id: number | null
  payment_method?: TransactionPaymentMethod
  created_by: number | null
  creator?: TransactionCreator
  is_reconciled: boolean
  created_at: string | null
  deleted_at?: string | null
  documents?: TransactionDocument[]
  donation?: TransactionDonation | null
  campaign_expense?: TransactionCampaignExpense | null
  general_expense?: TransactionGeneralExpense | null
  transfer?: TransactionTransfer | null
  bank_expense?: TransactionBankExpense | null
}

export interface AccountOption {
  id: number
  name: string
  currency_id?: number
}

export interface CurrencyOption {
  id: number
  code: string
  symbol: string
}

export interface PaymentMethodOption {
  id: number
  name: string
  code: string
}

export interface SelectOption {
  value: string
  label: string
}

export interface CampaignOption {
  id: number
  title_en: string
  title_ar: string
}

export interface UserOption {
  id: number
  name: string
}

export interface BeneficiaryOption {
  id: number
  display_name: string
}
