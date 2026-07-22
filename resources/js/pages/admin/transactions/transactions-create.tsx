import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { TransactionForm } from '@/components/admin/transactions/transaction-form'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import type {
  AccountOption,
  BeneficiaryOption,
  CampaignOption,
  CurrencyOption,
  PaymentMethodOption,
  SelectOption,
  UserOption,
} from '@/types/models/transaction'

type PageProps = {
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
  campaigns: CampaignOption[]
  users: UserOption[]
  beneficiaries: BeneficiaryOption[]
  transactionTypes: SelectOption[]
  directions: SelectOption[]
  defaultType?: string | null
}

export default function TransactionsCreate() {
  const props = usePage<PageProps>().props

  return (
    <>
      <Head title="New Transaction" />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={transactionsIndex.url()}>
              <ArrowLeft className="me-2 size-4" />
              Back to transactions
            </Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">New transaction</h2>
          <p className="text-muted-foreground">
            Record a ledger entry. Choose Transfer to capture recipient details.
          </p>
        </div>

        <TransactionForm
          {...props}
          submitUrl={route('admin.transactions.store')}
          method="post"
          submitLabel="Save & view"
        />
      </Main>
    </>
  )
}

TransactionsCreate.layout = {
  breadcrumbs: [
    { title: 'Transactions', href: '/admin/transactions' },
    { title: 'Create', href: '/admin/transactions/create' },
  ],
}
