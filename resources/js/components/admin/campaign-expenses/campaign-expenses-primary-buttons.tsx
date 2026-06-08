import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCampaignExpenses } from './campaign-expenses-provider'

export function CampaignExpensesPrimaryButtons() {
  const { setOpen } = useCampaignExpenses()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Record Expense</span>
      <Plus size={18} />
    </Button>
  )
}
