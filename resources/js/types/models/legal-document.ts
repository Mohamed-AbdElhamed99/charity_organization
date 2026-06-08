export type LegalDocumentType = 'terms' | 'privacy'

export interface LegalDocument {
  id: number
  type: LegalDocumentType
  title_ar: string
  title_en: string | null
  body_ar: string
  body_en: string | null
  updated_at: string | null
}

export interface SiteLegalDocument {
  type: LegalDocumentType
  title: string
  body: string
  meta_title: string
  meta_description: string
  updated_at: string | null
}
