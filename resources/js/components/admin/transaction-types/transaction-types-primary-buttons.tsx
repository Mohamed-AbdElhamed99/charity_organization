import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTransactionTypes } from './transaction-types-provider'

export function TransactionTypesPrimaryButtons() {
  const { setOpen } = useTransactionTypes()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add Transaction Type</span>
      <Plus size={18} />
    </Button>
  )
}
