export interface PaymentMethod {
  id: number
  name: string
  code: string
  is_active: boolean
  transactions_count?: number
  created_at: string
  deleted_at?: string | null
}
