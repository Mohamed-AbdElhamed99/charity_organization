import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useRoles } from './roles-provider'

export function RolesPrimaryButtons() {
  const { setOpen } = useRoles()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Role</span>
      <Plus size={18} />
    </Button>
  )
}
