import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { usePaymentMethods } from './payment-methods-provider'

export function PaymentMethodsPrimaryButtons() {
  const { setOpen } = usePaymentMethods()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Payment Method</span>
      <Plus size={18} />
    </Button>
  )
}
