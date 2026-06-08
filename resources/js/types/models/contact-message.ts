export interface ContactMessage {
  id: number
  fullname: string
  email: string
  phone: string | null
  subject: string
  message: string
  is_reviewed: boolean
  reviewed_by: number | null
  reviewer_name?: string | null
  reviewed_at: string | null
  review_notes: string | null
  created_at: string
  deleted_at?: string | null
}
