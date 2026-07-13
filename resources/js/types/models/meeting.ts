export type MeetingType =
  | 'board'
  | 'committee'
  | 'general_assembly'
  | 'field'
  | 'emergency'
  | 'other'

export type MeetingStatus =
  | 'scheduled'
  | 'in_progress'
  | 'completed'
  | 'cancelled'
  | 'postponed'

export type MeetingLocationType = 'physical' | 'online' | 'hybrid'

export type AttendanceStatus =
  | 'invited'
  | 'confirmed'
  | 'attended'
  | 'absent'
  | 'excused'

export type AttendeeRole = 'chair' | 'secretary' | 'member' | 'observer' | 'guest'

export type DecisionStatus =
  | 'pending'
  | 'in_progress'
  | 'completed'
  | 'cancelled'
  | 'deferred'

export type DecisionType =
  | 'resolution'
  | 'action_item'
  | 'recommendation'
  | 'policy'
  | 'other'

export type DecisionPriority = 'low' | 'medium' | 'high' | 'critical'

export type MinutesFormat = 'standard' | 'formal' | 'simplified'

export type MinutesLanguage = 'ar' | 'en' | 'bilingual'

export type SelectOption = {
  value: string
  label: string
}

export type MeetingAttendee = {
  id?: number
  name: string
  name_en?: string | null
  title?: string | null
  organization?: string | null
  email?: string | null
  phone?: string | null
  attendance_status: AttendanceStatus
  attendance_status_label?: string
  role: AttendeeRole
  role_label?: string
  signature_present?: boolean
  notes?: string | null
}

export type MeetingDecision = {
  id: number
  meeting_id?: number
  decision_number: string
  title: string
  description: string
  decision_type: DecisionType
  decision_type_label?: string
  status: DecisionStatus
  status_label?: string
  priority: DecisionPriority
  priority_label?: string
  assigned_to?: string | null
  due_date?: string | null
  completion_date?: string | null
  completion_notes?: string | null
  sort_order: number
  is_overdue: boolean
  created_at?: string
}

export type MeetingMinutes = {
  id: number
  meeting_id: number
  content: string
  summary?: string | null
  format: MinutesFormat
  format_label?: string
  language: MinutesLanguage
  language_label?: string
  version: number
  is_approved: boolean
  approved_by?: { id: number; name: string } | null
  approved_at?: string | null
  created_by?: { id: number; name: string } | null
  created_at?: string
  updated_at?: string
}

export type MeetingAttachment = {
  id: number
  meeting_id: number
  file_name: string
  file_path: string
  file_type: string
  file_size: number
  description?: string | null
  url: string
  uploaded_by?: { id: number; name: string } | null
  created_at?: string
}

export type MeetingCampaign = {
  id: number
  title_en: string
  title_ar?: string
  slug: string
  relationship_type?: string | null
  notes?: string | null
}

export type MeetingListItem = {
  id: number
  title: string
  title_en?: string | null
  meeting_number: string
  type: MeetingType
  type_label?: string
  status: MeetingStatus
  status_label?: string
  meeting_date: string
  formatted_date?: string
  start_time?: string | null
  end_time?: string | null
  location?: string | null
  location_type?: MeetingLocationType
  attendees_count?: number
  decisions_count?: number
  campaigns?: MeetingCampaign[]
  created_at?: string
}

export type Meeting = MeetingListItem & {
  duration?: string | null
  location_type_label?: string
  meeting_link?: string | null
  agenda?: string | null
  description?: string | null
  quorum_required?: number | null
  quorum_met: boolean
  chairperson?: string | null
  secretary?: string | null
  notes?: string | null
  attended_count?: number
  minutes?: MeetingMinutes | null
  decisions?: {data:MeetingDecision[]}
  attendees?: MeetingAttendee[]
  attachments?: MeetingAttachment[]
  campaign_ids?: number[]
  created_by?: { id: number; name: string } | null
  updated_by?: { id: number; name: string } | null
  updated_at?: string
}

export type MeetingStatistics = {
  total: number
  by_status: Record<string, number>
  by_type: Record<string, number>
  upcoming_count: number
  decisions_pending: number
}

export type MeetingFilters = {
  query?: string
  search?: string
  status?: string
  type?: string
  date_from?: string
  date_to?: string
  campaign_id?: string | number
  page?: number
  per_page?: number
}
