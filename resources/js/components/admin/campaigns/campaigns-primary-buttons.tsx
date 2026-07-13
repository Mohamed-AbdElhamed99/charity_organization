import { Link } from '@inertiajs/react'
import { Megaphone } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { create } from '@/routes/admin/campaigns'

export function CampaignsPrimaryButtons() {
  return (
    <Button className="space-x-1" asChild>
      <Link href={create.url()}>
        <span>Add Campaign</span>
        <Megaphone size={18} />
      </Link>
    </Button>
  )
}
