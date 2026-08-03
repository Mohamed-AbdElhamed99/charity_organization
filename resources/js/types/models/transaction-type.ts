export interface TransactionType {
  id: number
  name: string
  code: string
  description?: string | null
  is_active: boolean
  is_system: boolean
  transactions_count?: number
  created_at: string
  deleted_at?: string | null
}

export type TransactionTypeOption = {
  id: number
  name: string
  code: string
  is_system?: boolean
}
