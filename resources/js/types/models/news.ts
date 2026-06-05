export interface NewsCategory {
  id: number
  name_ar: string
  name_en: string
}

export interface NewsGalleryItem {
  id: number
  url: string
  mime_type: string
}

export interface News {
  id: number
  slug: string
  title_ar: string
  title_en: string
  subtitle_ar: string | null
  subtitle_en: string | null
  excerpt_ar: string | null
  excerpt_en: string | null
  body_ar: string | null
  body_en: string | null
  video_url: string | null
  category_id: number | null
  category_name: string | null
  is_active: boolean
  is_private: boolean
  published_at: string | null
  meta_title_ar: string | null
  meta_title_en: string | null
  meta_description_ar: string | null
  meta_description_en: string | null
  thumbnail: string
  main_media: string
  gallery: NewsGalleryItem[]
  created_at: string
  deleted_at?: string | null
}

export type NewsStatus = 'active' | 'inactive' | 'published' | 'draft'
