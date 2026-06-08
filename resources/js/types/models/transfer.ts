import type { TransferRecipientType } from '@/types/enums'
import type { Transaction } from '@/types/models/transaction'

export interface TransferCampaign {
  id: number
  title_en: string
  title_ar: string
}

export interface TransferBeneficiary {
  id: number
  display_name: string | null
}

export interface TransferUser {
  id: number
  name: string
}

export interface TransferCreator {
  id: number
  name: string
}

export interface Transfer {
  id: number
  transaction_id: number | null
  transaction?: Transaction
  campaign_id: number | null
  campaign?: TransferCampaign
  recipient_type: TransferRecipientType | null
  recipient_type_label: string | null
  recipient_name: string
  recipient_phone: string | null
  beneficiary_id: number | null
  beneficiary?: TransferBeneficiary
  user_id: number | null
  user?: TransferUser
  amount: number | string
  transfer_date: string | null
  purpose: string
  notes: string | null
  created_by: number | null
  creator?: TransferCreator
  created_at: string | null
  deleted_at?: string | null
}

export interface CampaignOption {
  id: number
  title_ar: string
  title_en: string
}
