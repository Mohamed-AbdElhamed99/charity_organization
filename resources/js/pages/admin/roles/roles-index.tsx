import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { CanAccess } from '@/components/can-access'
import { RolesDialogs } from '@/components/admin/roles/roles-dialogs'
import { RolesPrimaryButtons } from '@/components/admin/roles/roles-primary-buttons'
import { RolesProvider } from '@/components/admin/roles/roles-provider'
import { RolesTable } from '@/components/admin/roles/roles-table'
import { index as rolesIndex } from '@/routes/admin/roles'
import { type PermissionGroups, type Role } from '@/types/models/role'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  page?: number
  per_page?: number
}

type PageProps = {
  roles: Paginated<Role>
  permissions: string[]
  permissionGroups: PermissionGroups
  search: SearchParams
}

export default function RolesIndex() {
  const { roles, permissionGroups, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Roles" />
      <CanAccess permission="manage_roles">
        <RolesProvider>
          <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
            <div className="flex flex-wrap items-end justify-between gap-2">
              <div>
                <h2 className="text-2xl font-bold tracking-tight">Roles</h2>
                <p className="text-muted-foreground">
                  Manage roles and permission assignments.
                </p>
              </div>
              <RolesPrimaryButtons />
            </div>

            <RolesTable roles={roles} search={search} />
          </Main>

          <RolesDialogs permissionGroups={permissionGroups} />
        </RolesProvider>
      </CanAccess>
    </>
  )
}

RolesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Roles',
      href: rolesIndex.url(),
    },
  ],
}
