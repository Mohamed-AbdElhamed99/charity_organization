import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { cn } from '@/lib/utils'
import { callTypes } from '@/components/admin/users/data/data'
import { Main } from '@/components/layout/main'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { index as usersIndex } from '@/routes/admin/users'
import type { UserShow } from '@/types/models/user'

type PageProps = {
  user: UserShow
}

function DetailField({
  label,
  value,
}: {
  label: string
  value: React.ReactNode
}) {
  return (
    <div>
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className="font-medium">{value}</p>
    </div>
  )
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function formatRoleName(role: string): string {
  return role.replace(/_/g, ' ')
}

export default function UsersShow() {
  const { user } = usePage<PageProps>().props
  const statusColor = callTypes.get(user.status)

  return (
    <>
      <Head title={user.name} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-3">
          <Button variant="outline" size="sm" asChild>
            <Link href={usersIndex.url()}>
              <ArrowLeft className="me-1 h-4 w-4" />
              Back
            </Link>
          </Button>
          <Badge variant="outline" className={cn('capitalize', statusColor)}>
            {user.status}
          </Badge>
          {user.email_verified_at ? (
            <Badge variant="secondary">Email verified</Badge>
          ) : (
            <Badge variant="outline">Email unverified</Badge>
          )}
          {user.deleted_at && <Badge variant="destructive">Deleted</Badge>}
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <div className="flex items-start gap-4">
                <Avatar className="size-16">
                  <AvatarImage src={user.avatar} alt={user.name} />
                  <AvatarFallback className="text-lg">
                    {getInitials(user.name)}
                  </AvatarFallback>
                </Avatar>
                <div className="min-w-0 space-y-1">
                  <CardTitle>{user.name}</CardTitle>
                  <CardDescription>{user.email}</CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <DetailField label="Phone" value={user.phone ?? '—'} />
              <DetailField label="Status" value={user.status} />
              <DetailField label="Created At" value={user.created_at} />
              <DetailField
                label="Email Verified At"
                value={user.email_verified_at ?? '—'}
              />
              {user.deleted_at && (
                <DetailField label="Deleted At" value={user.deleted_at} />
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Personal Details</CardTitle>
              <CardDescription>
                Profile and location information for this user.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <DetailField label="National ID" value={user.national_id ?? '—'} />
              <DetailField label="Job" value={user.job ?? '—'} />
              <DetailField label="Birthdate" value={user.birthdate ?? '—'} />
              <DetailField
                label="Gender"
                value={user.gender ? user.gender.replace(/_/g, ' ') : '—'}
              />
              <DetailField label="Country" value={user.country_name ?? '—'} />
              <DetailField label="State" value={user.state_name ?? '—'} />
              <DetailField
                label="Address"
                value={user.address ?? '—'}
              />
              <DetailField label="Bio" value={user.bio ?? '—'} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Roles</CardTitle>
              <CardDescription>
                Assigned roles for this user account.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {user.roles.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {user.roles.map((role) => (
                    <Badge key={role} variant="outline" className="capitalize">
                      {formatRoleName(role)}
                    </Badge>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  No roles assigned to this user.
                </p>
              )}
              {/* Role assignment (attach/detach) can be added here later */}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Permissions</CardTitle>
              <CardDescription>
                Effective permissions granted through roles.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {user.permissions.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {user.permissions.map((permission) => (
                    <Badge
                      key={permission}
                      variant="secondary"
                      className="capitalize"
                    >
                      {formatRoleName(permission)}
                    </Badge>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  No permissions assigned to this user.
                </p>
              )}
            </CardContent>
          </Card>
        </div>

        <Button variant="outline" asChild>
          <Link href={usersIndex.url()}>Back to list</Link>
        </Button>
      </Main>
    </>
  )
}

UsersShow.layout = {
  breadcrumbs: [
    {
      title: 'Users',
      href: usersIndex.url(),
    },
    {
      title: 'View User',
    },
  ],
}
