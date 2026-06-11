import { useEffect } from 'react'
import { type FormDataConvertible, useForm } from '@inertiajs/react'
import { Info } from 'lucide-react'
import { Button } from '@/components/ui/button'
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
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import InputError from '@/components/input-error'
import type {
  Beneficiary,
  BeneficiaryType,
  GeoOptions,
} from '@/types/models/beneficiary'
import {
  emptyFamily,
  emptyIndividual,
  emptyOrganization,
  genderOptions,
  statusOptions,
  subtypeOptions,
} from './data/data'
import { FamilyMembersRepeater } from './family-members-repeater'

type BeneficiaryFormProps = {
  type: BeneficiaryType
  beneficiary?: Beneficiary
  geoOptions: GeoOptions
  submitLabel: string
  onSubmit: (url: string, method: 'post' | 'put') => void
  submitUrl: string
  method: 'post' | 'put'
  lockType?: boolean
}

function mapBeneficiaryToForm(beneficiary?: Beneficiary) {
  return {
    type: beneficiary?.type ?? 'individual',
    status: beneficiary?.status ?? 'pending_assessment',
    notes: beneficiary?.notes ?? '',
    individual: beneficiary?.individual
      ? {
          subtype: beneficiary.individual.subtype,
          first_name: beneficiary.individual.first_name,
          middle_name: beneficiary.individual.middle_name ?? '',
          last_name: beneficiary.individual.last_name,
          gender: beneficiary.individual.gender ?? '',
          birthdate: beneficiary.individual.birthdate ?? '',
          national_id: beneficiary.individual.national_id ?? '',
          phone: beneficiary.individual.phone ?? '',
          address: beneficiary.individual.address ?? '',
          country_id: beneficiary.individual.country_id?.toString() ?? '',
          state_id: beneficiary.individual.state_id?.toString() ?? '',
          health_status: beneficiary.individual.health_status ?? '',
          education_level: beneficiary.individual.education_level ?? '',
          marital_status: beneficiary.individual.marital_status ?? '',
          employment_status: beneficiary.individual.employment_status ?? '',
          monthly_income: beneficiary.individual.monthly_income?.toString() ?? '',
          date_of_father_death:
            beneficiary.individual.date_of_father_death ?? '',
          school_year: beneficiary.individual.school_year ?? '',
          sibling_number:
            beneficiary.individual.sibling_number?.toString() ?? '',
          behavior_notes: beneficiary.individual.behavior_notes ?? '',
          notes: beneficiary.individual.notes ?? '',
        }
      : emptyIndividual(),
    family: beneficiary?.family
      ? {
          household_name: beneficiary.family.household_name,
          national_id: beneficiary.family.national_id ?? '',
          phone: beneficiary.family.phone ?? '',
          address: beneficiary.family.address ?? '',
          village: beneficiary.family.village ?? '',
          country_id: beneficiary.family.country_id?.toString() ?? '',
          state_id: beneficiary.family.state_id?.toString() ?? '',
          social_status: beneficiary.family.social_status ?? '',
          total_members: beneficiary.family.total_members?.toString() ?? '',
          monthly_income: beneficiary.family.monthly_income?.toString() ?? '',
          housing_type: beneficiary.family.housing_type ?? '',
          housing_ownership: beneficiary.family.housing_ownership ?? '',
          monthly_rent: beneficiary.family.monthly_rent?.toString() ?? '',
          notes: beneficiary.family.notes ?? '',
          members:
            beneficiary.family.members?.map((member) => ({
              id: member.id,
              subtype: member.subtype,
              first_name: member.first_name,
              middle_name: member.middle_name ?? '',
              last_name: member.last_name ?? '',
              gender: member.gender ?? '',
              birthdate: member.birthdate ?? '',
              national_id: member.national_id ?? '',
              relation: member.relation ?? '',
              health_status: member.health_status ?? '',
              education_level: member.education_level ?? '',
              marital_status: member.marital_status ?? '',
              employment_status: member.employment_status ?? '',
              monthly_income: member.monthly_income?.toString() ?? '',
              date_of_father_death: member.date_of_father_death ?? '',
              school_year: member.school_year ?? '',
              sibling_number: member.sibling_number?.toString() ?? '',
              behavior_notes: member.behavior_notes ?? '',
            })) ?? [emptyFamilyMemberFromData()],
        }
      : emptyFamily(),
    organization: beneficiary?.organization
      ? {
          name: beneficiary.organization.name,
          organization_type: beneficiary.organization.organization_type ?? '',
          charity_number: beneficiary.organization.charity_number ?? '',
          phone: beneficiary.organization.phone ?? '',
          email: beneficiary.organization.email ?? '',
          address: beneficiary.organization.address ?? '',
          country_id: beneficiary.organization.country_id?.toString() ?? '',
          state_id: beneficiary.organization.state_id?.toString() ?? '',
          contact_person: beneficiary.organization.contact_person ?? '',
          contact_phone: beneficiary.organization.contact_phone ?? '',
          notes: beneficiary.organization.notes ?? '',
        }
      : emptyOrganization(),
  }
}

function emptyFamilyMemberFromData() {
  return {
    subtype: 'adult' as const,
    first_name: '',
    middle_name: '',
    last_name: '',
    gender: '',
    birthdate: '',
    national_id: '',
    relation: '',
    health_status: '',
    education_level: '',
    marital_status: '',
    employment_status: '',
    monthly_income: '',
    date_of_father_death: '',
    school_year: '',
    sibling_number: '',
    behavior_notes: '',
  }
}

function normalizePayload(data: ReturnType<typeof mapBeneficiaryToForm>) {
  const nullable = (value: string) => (value === '' ? null : value)
  const nullableNumber = (value: string) =>
    value === '' ? null : Number(value)

  return {
    type: data.type,
    status: data.status,
    notes: nullable(data.notes),
    individual:
      data.type === 'individual'
        ? {
            ...data.individual,
            gender: nullable(data.individual.gender),
            birthdate: nullable(data.individual.birthdate),
            national_id: nullable(data.individual.national_id),
            phone: nullable(data.individual.phone),
            address: nullable(data.individual.address),
            country_id: nullableNumber(data.individual.country_id),
            state_id: nullableNumber(data.individual.state_id),
            health_status: nullable(data.individual.health_status),
            education_level: nullable(data.individual.education_level),
            marital_status: nullable(data.individual.marital_status),
            employment_status: nullable(data.individual.employment_status),
            monthly_income: nullableNumber(data.individual.monthly_income),
            date_of_father_death: nullable(
              data.individual.date_of_father_death
            ),
            school_year: nullable(data.individual.school_year),
            sibling_number: nullableNumber(data.individual.sibling_number),
            behavior_notes: nullable(data.individual.behavior_notes),
            notes: nullable(data.individual.notes),
          }
        : undefined,
    family:
      data.type === 'family'
        ? {
            ...data.family,
            national_id: nullable(data.family.national_id),
            phone: nullable(data.family.phone),
            address: nullable(data.family.address),
            village: nullable(data.family.village),
            country_id: nullableNumber(data.family.country_id),
            state_id: nullableNumber(data.family.state_id),
            social_status: nullable(data.family.social_status),
            total_members: nullableNumber(data.family.total_members),
            monthly_income: nullableNumber(data.family.monthly_income),
            housing_type: nullable(data.family.housing_type),
            housing_ownership: nullable(data.family.housing_ownership),
            monthly_rent: nullableNumber(data.family.monthly_rent),
            notes: nullable(data.family.notes),
            members: data.family.members.map((member) => ({
              id: member.id,
              subtype: member.subtype,
              first_name: member.first_name,
              middle_name: nullable(member.middle_name),
              last_name: nullable(member.last_name),
              gender: nullable(member.gender),
              birthdate: nullable(member.birthdate),
              national_id: nullable(member.national_id),
              relation: nullable(member.relation),
              health_status: nullable(member.health_status),
              education_level: nullable(member.education_level),
              marital_status: nullable(member.marital_status),
              employment_status: nullable(member.employment_status),
              monthly_income: nullableNumber(member.monthly_income),
              date_of_father_death: nullable(member.date_of_father_death),
              school_year: nullable(member.school_year),
              sibling_number: nullableNumber(member.sibling_number),
              behavior_notes: nullable(member.behavior_notes),
            })),
          }
        : undefined,
    organization:
      data.type === 'organization'
        ? {
            ...data.organization,
            organization_type: nullable(data.organization.organization_type),
            charity_number: nullable(data.organization.charity_number),
            phone: nullable(data.organization.phone),
            email: nullable(data.organization.email),
            address: nullable(data.organization.address),
            country_id: nullableNumber(data.organization.country_id),
            state_id: nullableNumber(data.organization.state_id),
            contact_person: nullable(data.organization.contact_person),
            contact_phone: nullable(data.organization.contact_phone),
            notes: nullable(data.organization.notes),
          }
        : undefined,
  }
}

export function BeneficiaryForm({
  type,
  beneficiary,
  geoOptions,
  submitLabel,
  submitUrl,
  method,
  lockType = false,
}: BeneficiaryFormProps) {
  const form = useForm(mapBeneficiaryToForm(beneficiary))

  useEffect(() => {
    form.setData('type', type)
  }, [type])

  const statesForCountry = (countryId: string) =>
    geoOptions.states.filter(
      (state) => state.country_id.toString() === countryId
    )

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => normalizePayload(data) as FormDataConvertible)

    if (method === 'post') {
      form.post(submitUrl)
      return
    }

    form.put(submitUrl)
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      <section className="space-y-4 rounded-lg border p-4">
        <h3 className="text-lg font-semibold">Base Information</h3>

        {lockType && (
          <div className="grid gap-2">
            <div className="flex items-center gap-2">
              <Label>Type</Label>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Info className="size-4 text-muted-foreground" />
                  </TooltipTrigger>
                  <TooltipContent>
                    Beneficiary type cannot be changed after creation because
                    it would orphan the detail profile.
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
            <Input value={type} disabled readOnly className="capitalize" />
          </div>
        )}

        <div className="grid gap-4 md:grid-cols-2">
          <div className="grid gap-2">
            <Label htmlFor="status">Status</Label>
            <Select
              value={form.data.status}
              onValueChange={(value) => form.setData('status', value)}
            >
              <SelectTrigger id="status">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.status} />
          </div>
        </div>

        <div className="grid gap-2">
          <Label htmlFor="notes">Internal notes</Label>
          <Textarea
            id="notes"
            value={form.data.notes}
            onChange={(event) => form.setData('notes', event.target.value)}
            rows={3}
          />
          <InputError message={form.errors.notes} />
        </div>
      </section>

      {type === 'individual' && (
        <section className="space-y-4 rounded-lg border p-4">
          <h3 className="text-lg font-semibold">Individual Profile</h3>
          <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="individual-subtype">Subtype</Label>
              <Select
                value={form.data.individual.subtype}
                onValueChange={(value) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    subtype: value as 'adult' | 'child',
                  })
                }
              >
                <SelectTrigger id="individual-subtype">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {subtypeOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors['individual.subtype']} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="individual-first_name">First name</Label>
              <Input
                id="individual-first_name"
                value={form.data.individual.first_name}
                onChange={(event) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    first_name: event.target.value,
                  })
                }
                dir="auto"
              />
              <InputError message={form.errors['individual.first_name']} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="individual-last_name">Last name</Label>
              <Input
                id="individual-last_name"
                value={form.data.individual.last_name}
                onChange={(event) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    last_name: event.target.value,
                  })
                }
                dir="auto"
              />
              <InputError message={form.errors['individual.last_name']} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="individual-national_id">National ID</Label>
              <Input
                id="individual-national_id"
                value={form.data.individual.national_id}
                onChange={(event) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    national_id: event.target.value,
                  })
                }
                dir="ltr"
              />
              <InputError message={form.errors['individual.national_id']} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="individual-phone">Phone</Label>
              <Input
                id="individual-phone"
                value={form.data.individual.phone}
                onChange={(event) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    phone: event.target.value,
                  })
                }
                dir="ltr"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="individual-gender">Gender</Label>
              <Select
                value={form.data.individual.gender || undefined}
                onValueChange={(value) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    gender: value,
                  })
                }
              >
                <SelectTrigger id="individual-gender">
                  <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                  {genderOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid gap-2 md:col-span-2">
              <Label htmlFor="individual-address">Address</Label>
              <Input
                id="individual-address"
                value={form.data.individual.address}
                onChange={(event) =>
                  form.setData('individual', {
                    ...form.data.individual,
                    address: event.target.value,
                  })
                }
                dir="auto"
              />
            </div>
          </div>
        </section>
      )}

      {type === 'family' && (
        <>
          <section className="space-y-4 rounded-lg border p-4">
            <h3 className="text-lg font-semibold">Household Profile</h3>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="grid gap-2 md:col-span-2">
                <Label htmlFor="family-household_name">Household name</Label>
                <Input
                  id="family-household_name"
                  value={form.data.family.household_name}
                  onChange={(event) =>
                    form.setData('family', {
                      ...form.data.family,
                      household_name: event.target.value,
                    })
                  }
                  dir="auto"
                />
                <InputError message={form.errors['family.household_name']} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="family-phone">Phone</Label>
                <Input
                  id="family-phone"
                  value={form.data.family.phone}
                  onChange={(event) =>
                    form.setData('family', {
                      ...form.data.family,
                      phone: event.target.value,
                    })
                  }
                  dir="ltr"
                />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="family-national_id">Head national ID</Label>
                <Input
                  id="family-national_id"
                  value={form.data.family.national_id}
                  onChange={(event) =>
                    form.setData('family', {
                      ...form.data.family,
                      national_id: event.target.value,
                    })
                  }
                  dir="ltr"
                />
              </div>

              <div className="grid gap-2 md:col-span-2">
                <Label htmlFor="family-address">Address</Label>
                <Input
                  id="family-address"
                  value={form.data.family.address}
                  onChange={(event) =>
                    form.setData('family', {
                      ...form.data.family,
                      address: event.target.value,
                    })
                  }
                  dir="auto"
                />
              </div>
            </div>
          </section>

          <section className="rounded-lg border p-4">
            <FamilyMembersRepeater
              members={form.data.family.members}
              errors={form.errors}
              onChange={(members) =>
                form.setData('family', { ...form.data.family, members })
              }
            />
          </section>
        </>
      )}

      {type === 'organization' && (
        <section className="space-y-4 rounded-lg border p-4">
          <h3 className="text-lg font-semibold">Organization Profile</h3>
          <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2 md:col-span-2">
              <Label htmlFor="organization-name">Organization name</Label>
              <Input
                id="organization-name"
                value={form.data.organization.name}
                onChange={(event) =>
                  form.setData('organization', {
                    ...form.data.organization,
                    name: event.target.value,
                  })
                }
                dir="auto"
              />
              <InputError message={form.errors['organization.name']} />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="organization-phone">Phone</Label>
              <Input
                id="organization-phone"
                value={form.data.organization.phone}
                onChange={(event) =>
                  form.setData('organization', {
                    ...form.data.organization,
                    phone: event.target.value,
                  })
                }
                dir="ltr"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="organization-email">Email</Label>
              <Input
                id="organization-email"
                type="email"
                value={form.data.organization.email}
                onChange={(event) =>
                  form.setData('organization', {
                    ...form.data.organization,
                    email: event.target.value,
                  })
                }
                dir="ltr"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="organization-contact_person">Contact person</Label>
              <Input
                id="organization-contact_person"
                value={form.data.organization.contact_person}
                onChange={(event) =>
                  form.setData('organization', {
                    ...form.data.organization,
                    contact_person: event.target.value,
                  })
                }
                dir="auto"
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="organization-contact_phone">Contact phone</Label>
              <Input
                id="organization-contact_phone"
                value={form.data.organization.contact_phone}
                onChange={(event) =>
                  form.setData('organization', {
                    ...form.data.organization,
                    contact_phone: event.target.value,
                  })
                }
                dir="ltr"
              />
            </div>
          </div>
        </section>
      )}

      <div className="flex justify-end gap-2">
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Saving...' : submitLabel}
        </Button>
      </div>
    </form>
  )
}
