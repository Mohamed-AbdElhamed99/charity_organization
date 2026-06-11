export interface GeneralExpenseCategory {
  id: number
  name: string
  description?: string | null
  is_active: boolean
  expenses_count?: number
  created_at: string
  deleted_at?: string | null
}
