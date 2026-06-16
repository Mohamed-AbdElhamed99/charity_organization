import { useState } from 'react'
import { Head, Link, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { ArrowLeft, Pencil } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Main } from '@/components/layout/main'
import { AssessmentsTimeline } from '@/components/admin/beneficiaries/assessments-timeline'
import {
  formatDate,
  statusBadgeColors,
  typeBadgeColors,
} from '@/components/admin/beneficiaries/data/data'
import { StatusChangeDialog } from '@/components/admin/beneficiaries/status-change-dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  edit,
  index as beneficiariesIndex,
} from '@/routes/admin/beneficiaries'
import type {
  Beneficiary,
  BeneficiaryAssessment,
  BeneficiaryFamilyMember,
} from '@/types/models/beneficiary'

type PageProps = {
  beneficiary: Beneficiary
  assessments: BeneficiaryAssessment[]
  can: {
    update: boolean
    delete: boolean
    createAssessment: boolean
    viewSensitive: boolean
  }
}

type TabKey = 'details' | 'members' | 'assessments'

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

export default function BeneficiariesShow() {
  const { beneficiary, assessments, can } = usePage<PageProps>().props
  const [statusDialogOpen, setStatusDialogOpen] = useState(false)
  const [activeTab, setActiveTab] = useState<TabKey>('details')

  const typeColor = typeBadgeColors.get(beneficiary.type)
  const statusColor = statusBadgeColors.get(beneficiary.status)

  const tabs: { key: TabKey; label: string; visible: boolean }[] = [
    { key: 'details', label: 'Details', visible: true },
    {
      key: 'members',
      label: 'Family Members',
      visible: beneficiary.type === 'family',
    },
    { key: 'assessments', label: 'Assessments', visible: true },
  ]

  return (
    <>
      <Head title={beneficiary.code} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={beneficiariesIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to beneficiaries
            </Link>
          </Button>
        </div>

        <Card>
          <CardHeader>
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div className="space-y-2">
                <CardTitle className="font-mono">{beneficiary.code}</CardTitle>
                <CardDescription>{beneficiary.display_name}</CardDescription>
                <div className="flex flex-wrap gap-2">
                  <Badge variant="outline" className={cn('capitalize', typeColor)}>
                    {beneficiary.type_label}
                  </Badge>
                  <Badge
                    variant="outline"
                    className={cn('capitalize', statusColor)}
                  >
                    {beneficiary.status_label}
                  </Badge>
                </div>
              </div>

              <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" asChild>
                  <Link href={route('admin.beneficiaries.beneficiary-supports.create', beneficiary.id)}>
                    Record support
                  </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                  <Link href={route('admin.beneficiaries.support-report', beneficiary.id)}>
                    Support report
                  </Link>
                </Button>
                {can.update && (
                  <>
                    <Button variant="outline" size="sm" asChild>
                      <Link href={edit.url(beneficiary.id)}>
                        <Pencil className="me-2 size-4" />
                        Edit
                      </Link>
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setStatusDialogOpen(true)}
                    >
                      Change status
                    </Button>
                  </>
                )}
              </div>
            </div>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-3">
            <DetailField
              label="Primary contact"
              value={beneficiary.primary_contact}
            />
            <DetailField
              label="Created"
              value={formatDate(beneficiary.created_at)}
            />
            <DetailField
              label="Created by"
              value={beneficiary.creator?.name}
            />
            {!can.viewSensitive && (
              <p className="sm:col-span-3 text-sm text-amber-600">
                Sensitive fields are masked. Request field-level access to view
                full PII.
              </p>
            )}
          </CardContent>
        </Card>

        <div className="space-y-4">
          <div className="bg-muted inline-flex h-9 w-fit items-center rounded-lg p-1">
            {tabs
              .filter((tab) => tab.visible)
              .map((tab) => (
                <button
                  key={tab.key}
                  type="button"
                  onClick={() => setActiveTab(tab.key)}
                  className={cn(
                    'inline-flex items-center justify-center rounded-md px-3 py-1 text-sm font-medium transition-colors',
                    activeTab === tab.key
                      ? 'bg-background text-foreground shadow-sm'
                      : 'text-muted-foreground hover:text-foreground'
                  )}
                >
                  {tab.label}
                </button>
              ))}
          </div>

          {activeTab === 'details' && (
            <Card>
              <CardContent className="grid gap-4 pt-6 sm:grid-cols-2">
                {beneficiary.type === 'individual' && beneficiary.individual && (
                  <>
                    <DetailField
                      label="Full name"
                      value={beneficiary.individual.full_name}
                    />
                    <DetailField
                      label="National ID"
                      value={beneficiary.individual.national_id}
                    />
                    <DetailField
                      label="Phone"
                      value={beneficiary.individual.phone}
                    />
                    <DetailField
                      label="Address"
                      value={beneficiary.individual.address}
                    />
                    <DetailField
                      label="Health status"
                      value={beneficiary.individual.health_status}
                    />
                    <DetailField
                      label="Education"
                      value={beneficiary.individual.education_level}
                    />
                  </>
                )}

                {beneficiary.type === 'family' && beneficiary.family && (
                  <>
                    <DetailField
                      label="Household"
                      value={beneficiary.family.household_name}
                    />
                    <DetailField
                      label="Phone"
                      value={beneficiary.family.phone}
                    />
                    <DetailField
                      label="Address"
                      value={beneficiary.family.address}
                    />
                    <DetailField
                      label="Social status"
                      value={beneficiary.family.social_status}
                    />
                    <DetailField
                      label="Total members"
                      value={beneficiary.family.total_members}
                    />
                    <DetailField
                      label="Housing"
                      value={beneficiary.family.housing_type}
                    />
                  </>
                )}

                {beneficiary.type === 'organization' &&
                  beneficiary.organization && (
                    <>
                      <DetailField
                        label="Organization"
                        value={beneficiary.organization.name}
                      />
                      <DetailField
                        label="Type"
                        value={beneficiary.organization.organization_type}
                      />
                      <DetailField
                        label="Phone"
                        value={beneficiary.organization.phone}
                      />
                      <DetailField
                        label="Contact person"
                        value={beneficiary.organization.contact_person}
                      />
                      <DetailField
                        label="Email"
                        value={beneficiary.organization.email}
                      />
                      <DetailField
                        label="Address"
                        value={beneficiary.organization.address}
                      />
                    </>
                  )}
              </CardContent>
            </Card>
          )}

          {activeTab === 'members' &&
            beneficiary.type === 'family' &&
            beneficiary.family?.members && (
              <div className="grid gap-4 md:grid-cols-2">
                {beneficiary.family.members.map(
                  (member: BeneficiaryFamilyMember) => (
                    <Card key={member.id}>
                      <CardHeader>
                        <CardTitle className="text-base">
                          {member.full_name ?? member.first_name}
                        </CardTitle>
                        <CardDescription className="capitalize">
                          {member.relation ?? member.subtype}
                        </CardDescription>
                      </CardHeader>
                      <CardContent className="grid gap-2 text-sm">
                        <DetailField
                          label="National ID"
                          value={member.national_id}
                        />
                        <DetailField
                          label="Birthdate"
                          value={formatDate(member.birthdate)}
                        />
                        <DetailField
                          label="Health"
                          value={member.health_status}
                        />
                      </CardContent>
                    </Card>
                  )
                )}
              </div>
            )}

          {activeTab === 'assessments' && (
            <AssessmentsTimeline
              beneficiaryId={beneficiary.id}
              assessments={assessments}
              canCreate={can.createAssessment}
              canReview={can.update}
            />
          )}
        </div>
      </Main>

      <StatusChangeDialog
        open={statusDialogOpen}
        onOpenChange={setStatusDialogOpen}
        beneficiaryId={beneficiary.id}
        currentStatus={beneficiary.status}
      />
    </>
  )
}

BeneficiariesShow.layout = {
  breadcrumbs: [
    {
      title: 'Beneficiaries',
      href: beneficiariesIndex.url(),
    },
    {
      title: 'Details',
      href: '#',
    },
  ],
}
