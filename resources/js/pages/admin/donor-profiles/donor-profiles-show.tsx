import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { ArrowLeft } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Main } from '@/components/layout/main'
import {
  formatDate,
  typeBadgeColors,
} from '@/components/admin/donor-profiles/data/data'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { index as donorProfilesIndex } from '@/routes/admin/donor-profiles'
import type {
  DonorProfile,
  GeoOptions,
  SelectOption,
} from '@/types/models/donor-profile'

type PageProps = {
  donorProfile: DonorProfile
  geoOptions: GeoOptions
  typeOptions: SelectOption[]
}

function DetailField({
  label,
  value,
}: {
  label: string
  value: string | number | null | undefined
}) {
  return (
    <div>
      <dt className="text-sm text-muted-foreground">{label}</dt>
      <dd className="font-medium">{value ?? '—'}</dd>
    </div>
  )
}

export default function DonorProfilesShow() {
  const { donorProfile, geoOptions, typeOptions } = usePage<PageProps>().props
  const isDeleted = Boolean(donorProfile.deleted_at)
  const typeColor = typeBadgeColors.get(donorProfile.type)

  const form = useForm({
    type: donorProfile.type,
    organization_name: donorProfile.organization_name ?? '',
    address: donorProfile.address ?? '',
    country_id: donorProfile.country_id?.toString() ?? '',
    state_id: donorProfile.state_id?.toString() ?? '',
    notes: donorProfile.notes ?? '',
  })

  const statesForCountry = geoOptions.states.filter(
    (state) => state.country_id.toString() === form.data.country_id
  )

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      type: data.type,
      organization_name: data.organization_name || null,
      address: data.address || null,
      country_id: data.country_id ? Number(data.country_id) : null,
      state_id: data.state_id ? Number(data.state_id) : null,
      notes: data.notes || null,
    }))

    form.patch(route('admin.donor-profiles.update', donorProfile.id), {
      preserveScroll: true,
    })
  }

  return (
    <>
      <Head title={donorProfile.display_name} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={donorProfilesIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to donor profiles
            </Link>
          </Button>
        </div>

        <Card>
          <CardHeader>
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div className="space-y-2">
                <CardTitle>{donorProfile.display_name}</CardTitle>
                <CardDescription>Donor profile details</CardDescription>
                <div className="flex flex-wrap gap-2">
                  <Badge variant="outline" className={cn('capitalize', typeColor)}>
                    {donorProfile.type_label}
                  </Badge>
                  {isDeleted && (
                    <Badge variant="outline" className="border-red-200 text-red-700">
                      Deleted
                    </Badge>
                  )}
                </div>
              </div>
            </div>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-3">
            <DetailField
              label="Created"
              value={formatDate(donorProfile.created_at)}
            />
            <DetailField label="Country" value={donorProfile.country_name} />
            <DetailField label="State" value={donorProfile.state_name} />
          </CardContent>
        </Card>

        {donorProfile.user && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Linked user</CardTitle>
              <CardDescription>
                Account information from the linked donor user.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <DetailField label="Name" value={donorProfile.user.name} />
              <DetailField label="Email" value={donorProfile.user.email} />
              <DetailField label="Phone" value={donorProfile.user.phone} />
              <DetailField label="Status" value={donorProfile.user.status} />
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Profile</CardTitle>
            <CardDescription>
              Update donor profile fields. User account details are managed
              separately.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid gap-2">
                <Label htmlFor="type">Type</Label>
                <Select
                  value={form.data.type}
                  onValueChange={(value) => {
                    form.setData('type', value)
                    if (value !== 'organization') {
                      form.setData('organization_name', '')
                    }
                  }}
                  disabled={isDeleted}
                >
                  <SelectTrigger id="type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {typeOptions.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={form.errors.type} />
              </div>

              {form.data.type === 'organization' && (
                <div className="grid gap-2">
                  <Label htmlFor="organization_name">Organization name</Label>
                  <Input
                    id="organization_name"
                    value={form.data.organization_name}
                    onChange={(event) =>
                      form.setData('organization_name', event.target.value)
                    }
                    disabled={isDeleted}
                    required
                  />
                  <InputError message={form.errors.organization_name} />
                </div>
              )}

              <div className="grid gap-2">
                <Label htmlFor="address">Address</Label>
                <Textarea
                  id="address"
                  value={form.data.address}
                  onChange={(event) =>
                    form.setData('address', event.target.value)
                  }
                  rows={2}
                  disabled={isDeleted}
                />
                <InputError message={form.errors.address} />
              </div>

              <div className="grid gap-4 md:grid-cols-2">
                <div className="grid gap-2">
                  <Label htmlFor="country_id">Country</Label>
                  <Select
                    value={form.data.country_id}
                    onValueChange={(value) => {
                      form.setData({
                        ...form.data,
                        country_id: value,
                        state_id: '',
                      })
                    }}
                    disabled={isDeleted}
                  >
                    <SelectTrigger id="country_id">
                      <SelectValue placeholder="Select country" />
                    </SelectTrigger>
                    <SelectContent>
                      {geoOptions.countries.map((country) => (
                        <SelectItem
                          key={country.id}
                          value={country.id.toString()}
                        >
                          {country.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.country_id} />
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="state_id">State</Label>
                  <Select
                    value={form.data.state_id}
                    onValueChange={(value) => form.setData('state_id', value)}
                    disabled={isDeleted || !form.data.country_id}
                  >
                    <SelectTrigger id="state_id">
                      <SelectValue placeholder="Select state" />
                    </SelectTrigger>
                    <SelectContent>
                      {statesForCountry.map((state) => (
                        <SelectItem key={state.id} value={state.id.toString()}>
                          {state.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.state_id} />
                </div>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                  id="notes"
                  value={form.data.notes}
                  onChange={(event) => form.setData('notes', event.target.value)}
                  rows={3}
                  disabled={isDeleted}
                />
                <InputError message={form.errors.notes} />
              </div>

              <div className="flex justify-end">
                <Button type="submit" disabled={form.processing || isDeleted}>
                  Save changes
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </Main>
    </>
  )
}

DonorProfilesShow.layout = {
  breadcrumbs: [
    {
      title: 'Donor Profiles',
      href: donorProfilesIndex.url(),
    },
    {
      title: 'Details',
      href: '#',
    },
  ],
}
