import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useFaqs } from './faqs-provider'

export function FaqsPrimaryButtons() {
  const { setOpen, setCurrentRow } = useFaqs()

  return (
    <Button
      onClick={() => {
        setCurrentRow(null)
        setOpen('add')
      }}
    >
      <Plus className="me-1 h-4 w-4" />
      Add FAQ
    </Button>
  )
}
