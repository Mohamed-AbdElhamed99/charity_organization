import { Main } from '@/components/layout/main';
import { UsersTable } from '@/components/users/users-table';
import { UsersPrimaryButtons } from '@/components/users/users-primary-buttons';
import { Paginated } from '@/types/pagination';
import { User } from '@/types/models/user';

export default function UserIndex({users}:{users:Paginated<User[]>}) {
        <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
                <div className='flex flex-wrap items-end justify-between gap-2'>
                        <div>
                                <h2 className='text-2xl font-bold tracking-tight'>User List</h2>
                                <p className='text-muted-foreground'>
                                        Manage your users and their roles here.
                                </p>
                        </div>
                        <UsersPrimaryButtons />
                </div>
                <UsersTable data={users} search={search} navigate={navigate} />
        </Main>
}