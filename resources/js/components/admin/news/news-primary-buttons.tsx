import { Newspaper } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useNews } from './news-provider'

export function NewsPrimaryButtons() {
  const { setOpen } = useNews()

  return (
    <Button className="space-x-1" onClick={() => setOpen('add')}>
      <span>Add News</span>
      <Newspaper size={18} />
    </Button>
  )
}
