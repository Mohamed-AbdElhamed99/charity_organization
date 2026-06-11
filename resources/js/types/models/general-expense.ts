export interface GeneralExpenseTransaction {
  id: number
  transaction_date: string | null
  net_amount: number
  description: string | null
  reference_number: string | null
  is_reconciled: boolean
  account_id: number
  account_name: string | null
  payment_method_id: number | null
  payment_method_name: string | null
  currency_symbol: string | null
}

export interface GeneralExpense {
  id: number
  transaction_id: number | null
  name: string
  amount: number
  expense_date: string | null
  vendor_name: string | null
  is_recurring: boolean
  notes: string | null
  category_id: number | null
  category_name: string | null
  created_by?: number
  creator_name: string | null
  created_at?: string
  transaction: GeneralExpenseTransaction | null
}

export interface GeneralExpenseCategoryOption {
  id: number
  name: string
}

export interface GeneralExpenseAccountOption {
  id: number
  name: string
}

export interface GeneralExpensePaymentMethodOption {
  id: number
  name: string
  code: string
}
