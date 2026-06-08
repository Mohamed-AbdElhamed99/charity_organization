import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useAccounts } from './accounts-provider'

export function AccountsPrimaryButtons() {
  const { setOpen, setCurrentRow } = useAccounts()

  return (
    <Button
      onClick={() => {
        setCurrentRow(null)
        setOpen('add')
      }}
    >
      <Plus className="me-1 h-4 w-4" />
      Add Account
    </Button>
  )
}
