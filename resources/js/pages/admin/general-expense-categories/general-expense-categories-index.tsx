import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { GeneralExpenseCategoriesDialogs } from '@/components/admin/general-expense-categories/general-expense-categories-dialogs'
import { GeneralExpenseCategoriesPrimaryButtons } from '@/components/admin/general-expense-categories/general-expense-categories-primary-buttons'
import { GeneralExpenseCategoriesProvider } from '@/components/admin/general-expense-categories/general-expense-categories-provider'
import { GeneralExpenseCategoriesTable } from '@/components/admin/general-expense-categories/general-expense-categories-table'
import { index as generalExpenseCategoriesIndex } from '@/routes/admin/general-expense-categories'
import { type GeneralExpenseCategory } from '@/types/models/general-expense-category'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  categories: Paginated<GeneralExpenseCategory>
  search: SearchParams
}

export default function GeneralExpenseCategoriesIndex() {
  const { categories, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="General Expense Categories" />

      <GeneralExpenseCategoriesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                General Expense Categories
              </h2>
              <p className="text-muted-foreground">
                Manage categories for general organizational expenses.
              </p>
            </div>
            <GeneralExpenseCategoriesPrimaryButtons />
          </div>

          <GeneralExpenseCategoriesTable
            categories={categories}
            search={search}
          />
        </Main>

        <GeneralExpenseCategoriesDialogs />
      </GeneralExpenseCategoriesProvider>
    </>
  )
}

GeneralExpenseCategoriesIndex.layout = {
  breadcrumbs: [
    {
      title: 'General Expense Categories',
      href: generalExpenseCategoriesIndex.url(),
    },
  ],
}
