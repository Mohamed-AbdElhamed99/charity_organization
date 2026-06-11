import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { Main } from '@/components/layout/main'
import { BeneficiaryForm } from '@/components/admin/beneficiaries/beneficiary-form'
import { Button } from '@/components/ui/button'
import {
  edit,
  index as beneficiariesIndex,
  show,
  update,
} from '@/routes/admin/beneficiaries'
import type { Beneficiary, GeoOptions } from '@/types/models/beneficiary'

type PageProps = {
  beneficiary: Beneficiary
  geoOptions: GeoOptions
}

export default function BeneficiariesEdit() {
  const { beneficiary, geoOptions } = usePage<PageProps>().props

  return (
    <>
      <Head title={`Edit ${beneficiary.code}`} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={show.url(beneficiary.id)}>
              <ArrowLeft className="me-2 size-4" />
              Back to beneficiary
            </Link>
          </Button>
        </div>

        <div className="space-y-2">
          <h2 className="text-2xl font-bold tracking-tight">
            Edit {beneficiary.code}
          </h2>
          <p className="text-muted-foreground">
            Update beneficiary profile details. Type is locked after creation.
          </p>
        </div>

        <BeneficiaryForm
          type={beneficiary.type}
          beneficiary={beneficiary}
          geoOptions={geoOptions}
          submitLabel="Save changes"
          submitUrl={update.url(beneficiary.id)}
          method="put"
          lockType
        />
      </Main>
    </>
  )
}

BeneficiariesEdit.layout = {
  breadcrumbs: [
    {
      title: 'Beneficiaries',
      href: beneficiariesIndex.url(),
    },
    {
      title: 'Edit',
      href: edit.url(0),
    },
  ],
}
