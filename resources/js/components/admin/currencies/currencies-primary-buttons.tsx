import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCurrencies } from './currencies-provider'

export function CurrenciesPrimaryButtons() {
  const { setOpen } = useCurrencies()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Currency</span>
      <Plus size={18} />
    </Button>
  )
}
