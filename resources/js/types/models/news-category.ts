export interface NewsCategory {
  id: number
  name_ar: string
  name_en: string
  is_active: boolean
  news_count?: number
  created_at: string
  deleted_at?: string | null
}
