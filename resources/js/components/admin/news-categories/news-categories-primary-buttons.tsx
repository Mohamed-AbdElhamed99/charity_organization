import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useNewsCategories } from './news-categories-provider'

export function NewsCategoriesPrimaryButtons() {
  const { setOpen } = useNewsCategories()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Category</span>
      <Plus size={18} />
    </Button>
  )
}
