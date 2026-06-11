import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useGeneralExpenseCategories } from './general-expense-categories-provider'

export function GeneralExpenseCategoriesPrimaryButtons() {
  const { setOpen } = useGeneralExpenseCategories()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Category</span>
      <Plus size={18} />
    </Button>
  )
}
