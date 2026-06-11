import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { DonorProfilesDialogs } from '@/components/admin/donor-profiles/donor-profiles-dialogs'
import { DonorProfilesPrimaryButtons } from '@/components/admin/donor-profiles/donor-profiles-primary-buttons'
import { DonorProfilesProvider } from '@/components/admin/donor-profiles/donor-profiles-provider'
import { DonorProfilesTable } from '@/components/admin/donor-profiles/donor-profiles-table'
import { index as donorProfilesIndex } from '@/routes/admin/donor-profiles'
import type {
  AvailableDonorUser,
  DonorProfileListItem,
  GeoOptions,
  SelectOption,
} from '@/types/models/donor-profile'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  type?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  donorProfiles: Paginated<DonorProfileListItem>
  availableUsers: AvailableDonorUser[]
  typeOptions: SelectOption[]
  geoOptions: GeoOptions
  search: SearchParams
}

export default function DonorProfilesIndex() {
  const { donorProfiles, availableUsers, typeOptions, geoOptions, search } =
    usePage<PageProps>().props

  return (
    <>
      <Head title="Donor Profiles" />

      <DonorProfilesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Donor Profiles
              </h2>
              <p className="text-muted-foreground">
                Manage donor profile details linked to donor user accounts.
              </p>
            </div>
            <DonorProfilesPrimaryButtons />
          </div>

          <DonorProfilesTable
            donorProfiles={donorProfiles}
            typeOptions={typeOptions}
            search={search}
          />
        </Main>

        <DonorProfilesDialogs
          availableUsers={availableUsers}
          geoOptions={geoOptions}
          typeOptions={typeOptions}
        />
      </DonorProfilesProvider>
    </>
  )
}

DonorProfilesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Donor Profiles',
      href: donorProfilesIndex.url(),
    },
  ],
}
