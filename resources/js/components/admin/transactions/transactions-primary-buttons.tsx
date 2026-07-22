import { Link } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'

export function TransactionsPrimaryButtons() {
  return (
    <Button asChild>
      <Link href={route('admin.transactions.create')}>
        <Plus className="me-1 h-4 w-4" />
        Add Transaction
      </Link>
    </Button>
  )
}
