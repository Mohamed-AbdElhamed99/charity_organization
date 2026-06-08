export interface CampaignCategory {
  id: number
  name_ar: string
  name_en: string
  description: string | null
  is_active: boolean
  campaigns_count?: number
  created_at: string
  deleted_at?: string | null
}
