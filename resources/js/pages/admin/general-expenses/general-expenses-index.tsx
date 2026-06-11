import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { GeneralExpensesDialogs } from '@/components/admin/general-expenses/dialogs'
import { GeneralExpensesPrimaryButtons } from '@/components/admin/general-expenses/primary-buttons'
import { GeneralExpensesProvider } from '@/components/admin/general-expenses/provider'
import { GeneralExpensesTable } from '@/components/admin/general-expenses/table'
import { index as generalExpensesIndex } from '@/routes/admin/general-expenses'
import type {
  GeneralExpense,
  GeneralExpenseAccountOption,
  GeneralExpenseCategoryOption,
  GeneralExpensePaymentMethodOption,
} from '@/types/models/general-expense'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  category_id?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

type PageProps = {
  expenses: Paginated<GeneralExpense>
  categories: GeneralExpenseCategoryOption[]
  accounts: GeneralExpenseAccountOption[]
  paymentMethods: GeneralExpensePaymentMethodOption[]
  search: SearchParams
}

export default function GeneralExpensesIndex() {
  const { expenses, categories, accounts, paymentMethods, search } =
    usePage<PageProps>().props

  return (
    <>
      <Head title="General Expenses" />

      <GeneralExpensesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                General Expenses
              </h2>
              <p className="text-muted-foreground">
                View and record operational expenses across the organization.
              </p>
            </div>
            <GeneralExpensesPrimaryButtons />
          </div>

          <GeneralExpensesTable expenses={expenses} search={search} />
        </Main>

        <GeneralExpensesDialogs
          categories={categories}
          accounts={accounts}
          paymentMethods={paymentMethods}
        />
      </GeneralExpensesProvider>
    </>
  )
}

GeneralExpensesIndex.layout = {
  breadcrumbs: [
    {
      title: 'General Expenses',
      href: generalExpensesIndex.url(),
    },
  ],
}
