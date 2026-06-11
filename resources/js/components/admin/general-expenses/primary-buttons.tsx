import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useGeneralExpenses } from './provider'

export function GeneralExpensesPrimaryButtons() {
  const { setOpen } = useGeneralExpenses()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Record Expense</span>
      <Plus size={18} />
    </Button>
  )
}
