import { Head, Link, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { formatMoney } from '@/components/admin/transactions/data/data'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { index as transactionsIndex } from '@/routes/admin/transactions'
import type { Transaction, TransactionLinkItem } from '@/types/models/transaction'

type PageProps = {
  transaction: Transaction
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

function RelatedGroup({
  title,
  items,
  hrefFor,
}: {
  title: string
  items?: TransactionLinkItem[]
  hrefFor: (id: number) => string
}) {
  return (
    <div className="space-y-2">
      <p className="text-sm font-medium">{title}</p>
      {!items || items.length === 0 ? (
        <p className="text-sm text-muted-foreground">None</p>
      ) : (
        <ul className="space-y-1">
          {items.map((item) => (
            <li key={item.id}>
              <Link href={hrefFor(item.id)} className="underline">
                {item.label}
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

export default function TransactionsShow() {
  const { transaction } = usePage<PageProps>().props
  const symbol = transaction.currency?.symbol
  const originalSymbol = transaction.original_currency?.symbol

  return (
    <>
      <Head title={`Transaction #${transaction.id}`} />

      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-3">
          <Button variant="outline" size="sm" asChild>
            <Link href={transactionsIndex.url()}>
              <ArrowLeft className="me-1 h-4 w-4" />
              Back
            </Link>
          </Button>
          <Button variant="outline" size="sm" asChild>
            <Link href={route('admin.transactions.edit', transaction.id)}>
              Edit
            </Link>
          </Button>
          <Badge variant="outline" className="capitalize">
            {transaction.transaction_type_label}
          </Badge>
          <Badge variant="outline" className="capitalize">
            {transaction.direction}
          </Badge>
          {transaction.is_reconciled && (
            <Badge variant="secondary">Reconciled</Badge>
          )}
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Transaction Details</CardTitle>
              <CardDescription>
                Core ledger information for this entry.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <DetailField
                label="Date"
                value={transaction.transaction_date ?? '—'}
              />
              <DetailField
                label="Account"
                value={transaction.account?.name ?? `#${transaction.account_id}`}
              />
              <DetailField
                label="Ledger currency"
                value={
                  transaction.currency
                    ? `${transaction.currency.code} (${transaction.currency.symbol})`
                    : '—'
                }
              />
              <DetailField
                label="Payment Method"
                value={transaction.payment_method?.name ?? '—'}
              />
              {transaction.original_currency &&
                transaction.original_currency_id !== transaction.currency_id && (
                  <>
                    <DetailField
                      label="Original amount"
                      value={formatMoney(
                        transaction.original_amount,
                        originalSymbol,
                      )}
                    />
                    <DetailField
                      label="Exchange rate"
                      value={String(transaction.exchange_rate ?? '—')}
                    />
                  </>
                )}
              <DetailField
                label="Gross Amount"
                value={formatMoney(transaction.gross_amount, symbol)}
              />
              <DetailField
                label="Fee Amount"
                value={formatMoney(transaction.fee_amount, symbol)}
              />
              <DetailField
                label="Net Amount"
                value={formatMoney(transaction.net_amount, symbol)}
              />
              {(transaction.currency_equivalent_amounts?.length ?? 0) > 0 && (
                <div className="sm:col-span-2">
                  <p className="text-sm text-muted-foreground">
                    Currency equivalents
                  </p>
                  <ul className="mt-1 space-y-1">
                    {transaction.currency_equivalent_amounts?.map((row) => (
                      <li key={row.currency_code} className="font-medium">
                        {formatMoney(transaction.net_amount, symbol)} ={' '}
                        {formatMoney(row.amount, row.currency_code)}{' '}
                        <span className="text-sm font-normal text-muted-foreground">
                          (1 {transaction.currency?.code} = {row.equivalent}{' '}
                          {row.currency_code})
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              <DetailField
                label="Balance Before"
                value={formatMoney(transaction.balance_before, symbol)}
              />
              <DetailField
                label="Balance After"
                value={formatMoney(transaction.running_balance, symbol)}
              />
              <DetailField
                label="Reference"
                value={transaction.reference_number ?? '—'}
              />
              <DetailField
                label="Created By"
                value={transaction.creator?.name ?? '—'}
              />
              <DetailField
                label="Description"
                value={transaction.description ?? '—'}
              />
              <DetailField label="Notes" value={transaction.notes ?? '—'} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Linked Record</CardTitle>
              <CardDescription>
                Detail record associated with this transaction type.
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              {transaction.donation && (
                <>
                  <DetailField
                    label="Donor"
                    value={transaction.donation.donor_name ?? '—'}
                  />
                  <DetailField
                    label="Donation Amount"
                    value={formatMoney(transaction.donation.amount, symbol)}
                  />
                  <DetailField
                    label="Campaign ID"
                    value={transaction.donation.campaign_id ?? '—'}
                  />
                </>
              )}

              {transaction.campaign_expense && (
                <>
                  <DetailField
                    label="Campaign ID"
                    value={transaction.campaign_expense.campaign_id}
                  />
                  <DetailField
                    label="Expense Amount"
                    value={formatMoney(
                      transaction.campaign_expense.amount,
                      symbol,
                    )}
                  />
                  <DetailField
                    label="Expense Date"
                    value={transaction.campaign_expense.expense_date ?? '—'}
                  />
                </>
              )}

              {transaction.general_expense && (
                <>
                  {transaction.general_expense.name && (
                    <DetailField
                      label="Expense name"
                      value={transaction.general_expense.name}
                    />
                  )}
                  <DetailField
                    label="Expense Amount"
                    value={formatMoney(
                      transaction.general_expense.amount,
                      symbol,
                    )}
                  />
                  <DetailField
                    label="Expense Date"
                    value={transaction.general_expense.expense_date ?? '—'}
                  />
                </>
              )}

              {transaction.transfer && (
                <>
                  <DetailField
                    label="Recipient kind"
                    value={transaction.transfer.recipient_kind}
                  />
                  <DetailField
                    label="Recipient"
                    value={transaction.transfer.recipient_name ?? '—'}
                  />
                  {transaction.transfer.recipient_phone && (
                    <DetailField
                      label="Phone"
                      value={transaction.transfer.recipient_phone}
                    />
                  )}
                  <DetailField
                    label="Transfer Amount"
                    value={formatMoney(transaction.transfer.amount, symbol)}
                  />
                  <DetailField
                    label="Purpose"
                    value={transaction.transfer.purpose ?? '—'}
                  />
                </>
              )}

              {transaction.bank_expense && (
                <>
                  <DetailField
                    label="Bank Expense Amount"
                    value={formatMoney(transaction.bank_expense.amount, symbol)}
                  />
                  <DetailField
                    label="Expense Date"
                    value={transaction.bank_expense.expense_date ?? '—'}
                  />
                </>
              )}

              {!transaction.donation &&
                !transaction.campaign_expense &&
                !transaction.general_expense &&
                !transaction.transfer &&
                !transaction.bank_expense && (
                  <p className="text-sm text-muted-foreground">
                    No linked detail record for this transaction.
                  </p>
                )}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Related records</CardTitle>
            <CardDescription>
              Polymorphic links attached to this ledger entry.
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-6 sm:grid-cols-2">
            <RelatedGroup
              title="Beneficiaries"
              items={transaction.links?.beneficiaries}
              hrefFor={(id) => route('admin.beneficiaries.show', id)}
            />
            <RelatedGroup
              title="Campaigns"
              items={transaction.links?.campaigns}
              hrefFor={(id) => route('admin.campaigns.show', id)}
            />
            <RelatedGroup
              title="Meetings"
              items={transaction.links?.meetings}
              hrefFor={(id) => route('admin.meetings.show', id)}
            />
            <RelatedGroup
              title="General expenses"
              items={transaction.links?.general_expenses}
              hrefFor={() => route('admin.general-expenses.index')}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Documents</CardTitle>
            <CardDescription>
              Uploaded receipts and supporting files.
            </CardDescription>
          </CardHeader>
          <CardContent>
            {transaction.documents && transaction.documents.length > 0 ? (
              <ul className="space-y-2">
                {transaction.documents.map((document) => (
                  <li key={document.id}>
                    <a
                      href={document.url}
                      target="_blank"
                      rel="noreferrer"
                      className="underline"
                    >
                      {document.name}
                    </a>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-muted-foreground">No documents.</p>
            )}
          </CardContent>
        </Card>

        <Button variant="outline" asChild>
          <Link href={transactionsIndex.url()}>Back to list</Link>
        </Button>
      </Main>
    </>
  )
}

TransactionsShow.layout = {
  breadcrumbs: [
    {
      title: 'Transactions',
      href: transactionsIndex.url(),
    },
    {
      title: 'View Transaction',
    },
  ],
}
