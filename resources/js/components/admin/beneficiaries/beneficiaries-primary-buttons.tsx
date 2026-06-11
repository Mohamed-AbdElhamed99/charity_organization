import { Link } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { create } from '@/routes/admin/beneficiaries'

export function BeneficiariesPrimaryButtons() {
  return (
    <div className="flex gap-2">
      <Button asChild>
        <Link href={create.url()}>
          <Plus className="me-2 size-4" />
          New beneficiary
        </Link>
      </Button>
    </div>
  )
}
