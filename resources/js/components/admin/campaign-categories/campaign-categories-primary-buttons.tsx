import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCampaignCategories } from './campaign-categories-provider'

export function CampaignCategoriesPrimaryButtons() {
  const { setOpen } = useCampaignCategories()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Category</span>
      <Plus size={18} />
    </Button>
  )
}
