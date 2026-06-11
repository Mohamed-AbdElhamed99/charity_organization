import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useDonorProfiles } from './donor-profiles-provider'

export function DonorProfilesPrimaryButtons() {
  const { setOpen } = useDonorProfiles()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Donor Profile</span>
      <Plus size={18} />
    </Button>
  )
}
