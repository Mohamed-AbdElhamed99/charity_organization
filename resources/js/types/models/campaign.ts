import type { CampaignRecurrence, CampaignStatus } from '@/types/enums'

export interface CampaignGalleryItem {
  id: number
  url: string
  mime_type: string
}

export interface CampaignCategoryOption {
  id: number
  name_ar: string
  name_en: string
}

export interface Campaign {
  id: number
  slug: string
  title_ar: string
  title_en: string
  excerpt_ar: string | null
  excerpt_en: string | null
  description_ar: string | null
  description_en: string | null
  category_id: number | null
  category_name: string | null
  start_date: string | null
  end_date: string | null
  address: string | null
  country_id: number | null
  state_id: number | null
  lat: number | null
  lng: number | null
  budget: number
  donation_target: number | null
  status: CampaignStatus
  is_public: boolean
  open_donation_form: boolean
  is_repeated: CampaignRecurrence
  repeat_until: string | null
  meta_title_ar: string | null
  meta_title_en: string | null
  meta_description_ar: string | null
  meta_description_en: string | null
  cover_url: string
  gallery: CampaignGalleryItem[]
  meeting_ids?: number[]
  meetings?: Array<{
    id: number
    title: string
    meeting_number: string
  }>
  expenses_count?: number
  donations_count?: number
  total_donated?: number
  total_expenses?: number
  remaining_budget?: number
  donation_progress?: number | null
  created_at: string
  deleted_at?: string | null
}
