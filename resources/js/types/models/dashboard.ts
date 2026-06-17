export interface DonationsThisMonth {
  cents: number
  prior_month_cents: number
}

export interface NetBalance {
  cents: number
}

export interface DashboardStats {
  active_campaigns_count: number
  total_donations_this_month?: DonationsThisMonth
  net_balance?: NetBalance
}

export interface MonthlySummaryEntry {
  month: string
  donations: number
  expenses: number
}
