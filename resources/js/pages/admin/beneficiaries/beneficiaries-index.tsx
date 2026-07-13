import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { BeneficiariesDeleteDialog } from '@/components/admin/beneficiaries/beneficiaries-delete-dialog'
import { BeneficiariesPrimaryButtons } from '@/components/admin/beneficiaries/beneficiaries-primary-buttons'
import { BeneficiariesProvider } from '@/components/admin/beneficiaries/beneficiaries-provider'
import { BeneficiariesTable } from '@/components/admin/beneficiaries/beneficiaries-table'
import { index as beneficiariesIndex } from '@/routes/admin/beneficiaries'
import type {
  BeneficiaryListItem,
  GeoOptions,
  SelectOption,
} from '@/types/models/beneficiary'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  type?: string | string[]
  status?: string | string[]
  country_id?: string | string[]
  state_id?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  beneficiaries: Paginated<BeneficiaryListItem>
  search: SearchParams
  typeOptions: SelectOption[]
  statusOptions: SelectOption[]
  geoOptions: GeoOptions
}

export default function BeneficiariesIndex() {
  const { beneficiaries, search, typeOptions, statusOptions, geoOptions } =
    usePage<PageProps>().props

  return (
    <>
      <Head title="Beneficiaries" />

      <BeneficiariesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Beneficiaries
              </h2>
              <p className="text-muted-foreground">
                Manage individual, family, and organization beneficiaries.
              </p>
            </div>
            <BeneficiariesPrimaryButtons />
          </div>

          <BeneficiariesTable
            beneficiaries={beneficiaries}
            typeOptions={typeOptions}
            statusOptions={statusOptions}
            geoOptions={geoOptions}
            search={search}
          />
        </Main>

        <BeneficiariesDeleteDialog />
      </BeneficiariesProvider>
    </>
  )
}

BeneficiariesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Beneficiaries',
      href: beneficiariesIndex.url(),
    },
  ],
}
