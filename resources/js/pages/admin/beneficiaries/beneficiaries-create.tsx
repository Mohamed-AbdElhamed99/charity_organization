import { useState } from 'react'
import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { BeneficiaryForm } from '@/components/admin/beneficiaries/beneficiary-form'
import { BeneficiaryTypeSelector } from '@/components/admin/beneficiaries/beneficiary-type-selector'
import { Button } from '@/components/ui/button'
import { index as beneficiariesIndex, store } from '@/routes/admin/beneficiaries'
import type {
  BeneficiaryType,
  GeoOptions,
  SelectOption,
} from '@/types/models/beneficiary'

type PageProps = {
  typeOptions: SelectOption[]
  geoOptions: GeoOptions
}

export default function BeneficiariesCreate() {
  const { geoOptions } = usePage<PageProps>().props
  const [step, setStep] = useState<1 | 2>(1)
  const [selectedType, setSelectedType] = useState<BeneficiaryType | ''>('')

  return (
    <>
      <Head title="New Beneficiary" />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={beneficiariesIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to beneficiaries
            </Link>
          </Button>
        </div>

        <div className="space-y-2">
          <h2 className="text-2xl font-bold tracking-tight">
            New Beneficiary
          </h2>
          <p className="text-muted-foreground">
            Step {step} of 2 —{' '}
            {step === 1 ? 'Choose beneficiary type' : 'Complete profile details'}
          </p>
        </div>

        {step === 1 && (
          <div className="space-y-6">
            <BeneficiaryTypeSelector
              value={selectedType}
              onChange={setSelectedType}
            />
            <div className="flex justify-end">
              <Button
                disabled={!selectedType}
                onClick={() => setStep(2)}
              >
                Continue
              </Button>
            </div>
          </div>
        )}

        {step === 2 && selectedType && (
          <div className="space-y-4">
            <Button variant="outline" size="sm" onClick={() => setStep(1)}>
              Change type
            </Button>
            <BeneficiaryForm
              type={selectedType}
              geoOptions={geoOptions}
              submitLabel="Create beneficiary"
              submitUrl={store.url()}
              method="post"
            />
          </div>
        )}
      </Main>
    </>
  )
}

BeneficiariesCreate.layout = {
  breadcrumbs: [
    {
      title: 'Beneficiaries',
      href: beneficiariesIndex.url(),
    },
    {
      title: 'New',
      href: '#',
    },
  ],
}
