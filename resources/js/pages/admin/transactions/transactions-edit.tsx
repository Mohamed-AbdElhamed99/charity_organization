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
  Transaction,
  UserOption,
} from '@/types/models/transaction'

type PageProps = {
  transaction: Transaction
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
  campaigns: CampaignOption[]
  users: UserOption[]
  beneficiaries: BeneficiaryOption[]
  transactionTypes: SelectOption[]
  directions: SelectOption[]
}

export default function TransactionsEdit() {
  const { transaction, ...props } = usePage<PageProps>().props

  return (
    <>
      <Head title={`Edit Transaction #${transaction.id}`} />
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
          <h2 className="text-2xl font-bold tracking-tight">
            Edit transaction #{transaction.id}
          </h2>
          <p className="text-muted-foreground">
            Update ledger details, transfer recipient, FX, and documents.
          </p>
        </div>

        <TransactionForm
          {...props}
          transaction={transaction}
          submitUrl={route('admin.transactions.update', transaction.id)}
          method="put"
          submitLabel="Save changes"
        />
      </Main>
    </>
  )
}

TransactionsEdit.layout = {
  breadcrumbs: [
    { title: 'Transactions', href: '/admin/transactions' },
    { title: 'Edit' },
  ],
}
