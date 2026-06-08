export interface Account {
  id: number
  name: string
  account_number: string | null
  bank_name: string | null
  bank_branch: string | null
  currency_id: number
  currency: {
    id: number
    code: string
    symbol: string
  } | null
  type: 'bank' | 'cash' | 'digital'
  type_label: string
  opening_balance: number
  is_active: boolean
  notes: string | null
  created_at: string
  deleted_at?: string | null
}

export interface AccountTypeOption {
  value: 'bank' | 'cash' | 'digital'
  label: string
}

export interface CurrencyOption {
  id: number
  code: string
  symbol: string
  name: string
}
