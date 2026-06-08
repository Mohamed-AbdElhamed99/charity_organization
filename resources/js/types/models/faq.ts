export interface Faq {
  id: number
  question_ar: string
  question_en: string | null
  answer_ar: string
  answer_en: string | null
  sort_order: number
  is_published: boolean
  created_at: string
  deleted_at?: string | null
}

export interface SiteFaq {
  id: number
  question: string
  answer: string
  sort_order: number
}
