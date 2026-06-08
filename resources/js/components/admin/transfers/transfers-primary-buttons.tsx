import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTransfers } from './transfers-provider'

export function TransfersPrimaryButtons() {
  const { setOpen } = useTransfers()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Record Transfer</span>
      <Plus size={18} />
    </Button>
  )
}
