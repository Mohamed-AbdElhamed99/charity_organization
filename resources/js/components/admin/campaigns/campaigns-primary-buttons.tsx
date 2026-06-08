import { Megaphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCampaigns } from './campaigns-provider'

export function CampaignsPrimaryButtons() {
  const { setOpen } = useCampaigns()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Campaign</span>
      <Megaphone size={18} />
    </Button>
  )
}
