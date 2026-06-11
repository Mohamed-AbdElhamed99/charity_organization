import { cn } from '@/lib/utils'
import { Building2, Home, User } from 'lucide-react'
import type { BeneficiaryType } from '@/types/models/beneficiary'

type BeneficiaryTypeSelectorProps = {
  value: BeneficiaryType | ''
  onChange: (type: BeneficiaryType) => void
}

const cards: {
  type: BeneficiaryType
  title: string
  description: string
  icon: typeof User
}[] = [
  {
    type: 'individual',
    title: 'Individual',
    description: 'Adult or child beneficiary with a personal profile.',
    icon: User,
  },
  {
    type: 'family',
    title: 'Family / Household',
    description: 'Household unit with a head and inline family members.',
    icon: Home,
  },
  {
    type: 'organization',
    title: 'Organization',
    description: 'Partner organization such as a hospital or care home.',
    icon: Building2,
  },
]

export function BeneficiaryTypeSelector({
  value,
  onChange,
}: BeneficiaryTypeSelectorProps) {
  return (
    <div className="grid gap-4 md:grid-cols-3">
      {cards.map((card) => {
        const Icon = card.icon
        const selected = value === card.type

        return (
          <button
            key={card.type}
            type="button"
            onClick={() => onChange(card.type)}
            className={cn(
              'flex flex-col items-start gap-3 rounded-lg border p-6 text-start transition-colors',
              selected
                ? 'border-primary bg-primary/5 ring-2 ring-primary'
                : 'hover:border-primary/50 hover:bg-muted/50'
            )}
          >
            <Icon className="size-8 text-primary" />
            <div>
              <h3 className="font-semibold">{card.title}</h3>
              <p className="mt-1 text-sm text-muted-foreground">
                {card.description}
              </p>
            </div>
          </button>
        )
      })}
    </div>
  )
}
