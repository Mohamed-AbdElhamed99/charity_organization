import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTransactions } from './transactions-provider'

export function TransactionsPrimaryButtons() {
  const { setOpen, setCurrentRow } = useTransactions()

  return (
    <Button
      onClick={() => {
        setCurrentRow(null)
        setOpen('add')
      }}
    >
      <Plus className="me-1 h-4 w-4" />
      Add Transaction
    </Button>
  )
}
