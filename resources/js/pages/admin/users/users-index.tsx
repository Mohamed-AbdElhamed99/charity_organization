import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { UsersDialogs } from '@/components/admin/users/users-dialogs'
import { UsersPrimaryButtons } from '@/components/admin/users/users-primary-buttons'
import { UsersProvider } from '@/components/admin/users/users-provider'
import { UsersTable } from '@/components/admin/users/users-table'
import { index as usersIndex } from '@/routes/admin/users'
import type { User } from '@/types/models/user' 
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  role?: string | string[]
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  users: Paginated<User>
  roles: string[]
  search: SearchParams
}

export default function UsersIndex() {
  const { users, roles, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Users" />

      <UsersProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">User List</h2>
              <p className="text-muted-foreground">
                Manage your users and their roles here.
              </p>
            </div>
            <UsersPrimaryButtons />
          </div>

          <UsersTable users={users} roles={roles} search={search} />
        </Main>

        <UsersDialogs roles={roles} />
      </UsersProvider>
    </>
  )
}

UsersIndex.layout = {
  breadcrumbs: [
    {
      title: 'Users',
      href: usersIndex.url(),
    },
  ],
}
