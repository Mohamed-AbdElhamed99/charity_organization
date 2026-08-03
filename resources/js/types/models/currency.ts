export interface Currency {
  id: number
  code: string
  name: string
  symbol: string | null
  is_active: boolean
  is_default: boolean
  accounts_count?: number
  transactions_count?: number
  created_at: string
}
