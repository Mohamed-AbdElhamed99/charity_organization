import { useMemo, useState } from 'react'
import { useForm } from '@inertiajs/react'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
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

type TransactionFormProps = {
  transaction?: Transaction
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
  campaigns: CampaignOption[]
  users: UserOption[]
  beneficiaries: BeneficiaryOption[]
  transactionTypes: SelectOption[]
  directions: SelectOption[]
  defaultType?: string | null
  submitUrl: string
  method: 'post' | 'put'
  submitLabel: string
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

function recipientKindFromTransaction(transaction?: Transaction): string {
  return transaction?.transfer?.recipient_kind ?? 'other'
}

export function TransactionForm({
  transaction,
  accounts,
  currencies,
  paymentMethods,
  campaigns,
  users,
  beneficiaries,
  transactionTypes,
  directions,
  defaultType,
  submitUrl,
  method,
  submitLabel,
}: TransactionFormProps) {
  const [removeDocumentIds, setRemoveDocumentIds] = useState<number[]>([])

  const form = useForm({
    account_id: transaction ? String(transaction.account_id) : '',
    transaction_type:
      transaction?.transaction_type ?? defaultType ?? 'donation',
    direction: transaction?.direction ?? 'in',
    gross_amount: transaction ? String(transaction.gross_amount) : '',
    fee_amount: transaction ? String(transaction.fee_amount) : '0',
    transaction_date:
      transaction?.transaction_date ?? todayDateInputValue(),
    reference_number: transaction?.reference_number ?? '',
    description: transaction?.description ?? '',
    notes: transaction?.notes ?? '',
    payment_method_id: transaction?.payment_method_id
      ? String(transaction.payment_method_id)
      : '',
    original_currency_id: transaction?.original_currency_id
      ? String(transaction.original_currency_id)
      : '',
    original_amount: transaction?.original_amount
      ? String(transaction.original_amount)
      : '',
    exchange_rate: transaction?.exchange_rate
      ? String(transaction.exchange_rate)
      : '1',
    documents: [] as File[],
    remove_document_ids: [] as number[],
    transfer: {
      recipient_kind: recipientKindFromTransaction(transaction),
      recipient_id: transaction?.transfer?.recipient_id
        ? String(transaction.transfer.recipient_id)
        : '',
      recipient_label: transaction?.transfer?.recipient_label ?? '',
      recipient_phone: transaction?.transfer?.recipient_phone ?? '',
      purpose: transaction?.transfer?.purpose ?? '',
      campaign_id: transaction?.transfer?.campaign_id
        ? String(transaction.transfer.campaign_id)
        : '',
      transfer_date:
        transaction?.transfer?.transfer_date ??
        transaction?.transaction_date ??
        todayDateInputValue(),
      notes: transaction?.transfer?.notes ?? '',
    },
  })

  const selectedAccount = useMemo(
    () => accounts.find((account) => String(account.id) === form.data.account_id),
    [accounts, form.data.account_id],
  )

  const accountCurrency = useMemo(
    () =>
      currencies.find(
        (currency) => currency.id === selectedAccount?.currency_id,
      ),
    [currencies, selectedAccount],
  )

  const originalCurrency = useMemo(
    () =>
      currencies.find(
        (currency) => String(currency.id) === form.data.original_currency_id,
      ) ?? accountCurrency,
    [currencies, form.data.original_currency_id, accountCurrency],
  )

  const isTransfer = form.data.transaction_type === 'transfer'
  const needsFx =
    !!form.data.original_currency_id &&
    !!selectedAccount?.currency_id &&
    String(selectedAccount.currency_id) !== form.data.original_currency_id

  const convertedPreview = useMemo(() => {
    const original = Number(form.data.original_amount || form.data.gross_amount || 0)
    const rate = Number(form.data.exchange_rate || 1)
    if (!needsFx) {
      return original
    }
    return Math.round(original * rate * 100) / 100
  }, [
    form.data.original_amount,
    form.data.gross_amount,
    form.data.exchange_rate,
    needsFx,
  ])

  const existingDocuments = (transaction?.documents ?? []).filter(
    (document) => !removeDocumentIds.includes(document.id),
  )

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const payloadDirection = isTransfer ? 'out' : form.data.direction
    const ledgerGross = needsFx
      ? String(convertedPreview)
      : form.data.original_amount || form.data.gross_amount

    form.transform((data) => ({
      ...data,
      direction: payloadDirection,
      gross_amount: ledgerGross,
      original_currency_id:
        data.original_currency_id ||
        (selectedAccount?.currency_id
          ? String(selectedAccount.currency_id)
          : ''),
      original_amount: data.original_amount || data.gross_amount,
      exchange_rate: needsFx ? data.exchange_rate : '1',
      remove_document_ids: removeDocumentIds,
      transfer: isTransfer ? data.transfer : undefined,
      payment_method_id: data.payment_method_id || null,
    }))

    const options = {
      forceFormData: true,
      preserveScroll: true,
    }

    if (method === 'put') {
      form.put(submitUrl, options)
    } else {
      form.post(submitUrl, options)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      <section className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="account_id">Bank account</Label>
          <Select
            value={form.data.account_id}
            onValueChange={(value) => {
              form.setData('account_id', value)
              const account = accounts.find((item) => String(item.id) === value)
              if (account?.currency_id && !form.data.original_currency_id) {
                form.setData('original_currency_id', String(account.currency_id))
              }
            }}
          >
            <SelectTrigger id="account_id">
              <SelectValue placeholder="Select account" />
            </SelectTrigger>
            <SelectContent>
              {accounts.map((account) => (
                <SelectItem key={account.id} value={String(account.id)}>
                  {account.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.account_id} />
        </div>

        <div className="space-y-2">
          <Label htmlFor="transaction_type">Type</Label>
          <Select
            value={form.data.transaction_type}
            onValueChange={(value) => {
              form.setData('transaction_type', value)
              if (value === 'transfer') {
                form.setData('direction', 'out')
              }
            }}
          >
            <SelectTrigger id="transaction_type">
              <SelectValue placeholder="Select type" />
            </SelectTrigger>
            <SelectContent>
              {transactionTypes.map((type) => (
                <SelectItem key={type.value} value={type.value}>
                  {type.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.transaction_type} />
        </div>

        {!isTransfer && (
          <div className="space-y-2">
            <Label htmlFor="direction">Direction</Label>
            <Select
              value={form.data.direction}
              onValueChange={(value) => form.setData('direction', value)}
            >
              <SelectTrigger id="direction">
                <SelectValue placeholder="Select direction" />
              </SelectTrigger>
              <SelectContent>
                {directions.map((direction) => (
                  <SelectItem key={direction.value} value={direction.value}>
                    {direction.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.direction} />
          </div>
        )}

        <div className="space-y-2">
          <Label htmlFor="transaction_date">Date</Label>
          <Input
            id="transaction_date"
            type="date"
            value={form.data.transaction_date}
            onChange={(event) =>
              form.setData('transaction_date', event.target.value)
            }
          />
          <InputError message={form.errors.transaction_date} />
        </div>

        <div className="space-y-2">
          <Label htmlFor="payment_method_id">Payment method</Label>
          <Select
            value={form.data.payment_method_id || '__none'}
            onValueChange={(value) =>
              form.setData(
                'payment_method_id',
                value === '__none' ? '' : value,
              )
            }
          >
            <SelectTrigger id="payment_method_id">
              <SelectValue placeholder="Optional" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none">None</SelectItem>
              {paymentMethods.map((method) => (
                <SelectItem key={method.id} value={String(method.id)}>
                  {method.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.payment_method_id} />
        </div>

        <div className="space-y-2">
          <Label htmlFor="reference_number">Reference</Label>
          <Input
            id="reference_number"
            value={form.data.reference_number}
            onChange={(event) =>
              form.setData('reference_number', event.target.value)
            }
          />
          <InputError message={form.errors.reference_number} />
        </div>
      </section>

      <section className="space-y-4 rounded-lg border p-4">
        <div>
          <h3 className="font-semibold">Amounts & currency</h3>
          <p className="text-sm text-muted-foreground">
            Ledger posts in the bank account currency
            {accountCurrency ? ` (${accountCurrency.code})` : ''}. Enter an
            original currency and rate when converting.
          </p>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="original_currency_id">Original currency</Label>
            <Select
              value={
                form.data.original_currency_id ||
                (accountCurrency ? String(accountCurrency.id) : '')
              }
              onValueChange={(value) =>
                form.setData('original_currency_id', value)
              }
            >
              <SelectTrigger id="original_currency_id">
                <SelectValue placeholder="Select currency" />
              </SelectTrigger>
              <SelectContent>
                {currencies.map((currency) => (
                  <SelectItem key={currency.id} value={String(currency.id)}>
                    {currency.code} ({currency.symbol})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <InputError message={form.errors.original_currency_id} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="original_amount">
              Amount ({originalCurrency?.code ?? 'original'})
            </Label>
            <Input
              id="original_amount"
              type="number"
              step="0.01"
              min="0"
              value={form.data.original_amount || form.data.gross_amount}
              onChange={(event) => {
                form.setData('original_amount', event.target.value)
                form.setData('gross_amount', event.target.value)
              }}
            />
            <InputError message={form.errors.original_amount} />
            <InputError message={form.errors.gross_amount} />
          </div>

          {needsFx && (
            <div className="space-y-2">
              <Label htmlFor="exchange_rate">
                Exchange rate to {accountCurrency?.code}
              </Label>
              <Input
                id="exchange_rate"
                type="number"
                step="0.00000001"
                min="0"
                value={form.data.exchange_rate}
                onChange={(event) =>
                  form.setData('exchange_rate', event.target.value)
                }
              />
              <InputError message={form.errors.exchange_rate} />
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="fee_amount">Fee ({accountCurrency?.code})</Label>
            <Input
              id="fee_amount"
              type="number"
              step="0.01"
              min="0"
              value={form.data.fee_amount}
              onChange={(event) => form.setData('fee_amount', event.target.value)}
            />
            <InputError message={form.errors.fee_amount} />
          </div>

          <div className="space-y-2 md:col-span-2">
            <Label>Ledger amount ({accountCurrency?.code ?? 'account'})</Label>
            <p className="text-lg font-semibold">
              {convertedPreview.toFixed(2)} {accountCurrency?.code ?? ''}
            </p>
          </div>
        </div>
      </section>

      {isTransfer && (
        <section className="space-y-4 rounded-lg border p-4">
          <div>
            <h3 className="font-semibold">Transfer details</h3>
            <p className="text-sm text-muted-foreground">
              Choose a user or beneficiary, or enter a free-text recipient.
            </p>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label>Recipient kind</Label>
              <Select
                value={form.data.transfer.recipient_kind}
                onValueChange={(value) =>
                  form.setData('transfer', {
                    ...form.data.transfer,
                    recipient_kind: value,
                    recipient_id: '',
                    recipient_label: '',
                  })
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="user">User / Staff</SelectItem>
                  <SelectItem value="beneficiary">Beneficiary</SelectItem>
                  <SelectItem value="other">Other (free text)</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors['transfer.recipient_kind']} />
            </div>

            {form.data.transfer.recipient_kind === 'user' && (
              <div className="space-y-2">
                <Label>User</Label>
                <Select
                  value={form.data.transfer.recipient_id}
                  onValueChange={(value) =>
                    form.setData('transfer', {
                      ...form.data.transfer,
                      recipient_id: value,
                    })
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select user" />
                  </SelectTrigger>
                  <SelectContent>
                    {users.map((user) => (
                      <SelectItem key={user.id} value={String(user.id)}>
                        {user.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={form.errors['transfer.recipient_id']} />
              </div>
            )}

            {form.data.transfer.recipient_kind === 'beneficiary' && (
              <div className="space-y-2">
                <Label>Beneficiary</Label>
                <Select
                  value={form.data.transfer.recipient_id}
                  onValueChange={(value) =>
                    form.setData('transfer', {
                      ...form.data.transfer,
                      recipient_id: value,
                    })
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select beneficiary" />
                  </SelectTrigger>
                  <SelectContent>
                    {beneficiaries.map((beneficiary) => (
                      <SelectItem
                        key={beneficiary.id}
                        value={String(beneficiary.id)}
                      >
                        {beneficiary.display_name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={form.errors['transfer.recipient_id']} />
              </div>
            )}

            {form.data.transfer.recipient_kind === 'other' && (
              <>
                <div className="space-y-2">
                  <Label htmlFor="recipient_label">Recipient name</Label>
                  <Input
                    id="recipient_label"
                    value={form.data.transfer.recipient_label}
                    onChange={(event) =>
                      form.setData('transfer', {
                        ...form.data.transfer,
                        recipient_label: event.target.value,
                      })
                    }
                  />
                  <InputError
                    message={form.errors['transfer.recipient_label']}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="recipient_phone">Phone</Label>
                  <Input
                    id="recipient_phone"
                    value={form.data.transfer.recipient_phone}
                    onChange={(event) =>
                      form.setData('transfer', {
                        ...form.data.transfer,
                        recipient_phone: event.target.value,
                      })
                    }
                  />
                </div>
              </>
            )}

            <div className="space-y-2">
              <Label htmlFor="purpose">Purpose</Label>
              <Input
                id="purpose"
                value={form.data.transfer.purpose}
                onChange={(event) =>
                  form.setData('transfer', {
                    ...form.data.transfer,
                    purpose: event.target.value,
                  })
                }
              />
              <InputError message={form.errors['transfer.purpose']} />
            </div>

            <div className="space-y-2">
              <Label>Campaign (optional)</Label>
              <Select
                value={form.data.transfer.campaign_id || '__none'}
                onValueChange={(value) =>
                  form.setData('transfer', {
                    ...form.data.transfer,
                    campaign_id: value === '__none' ? '' : value,
                  })
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Optional" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none">None</SelectItem>
                  {campaigns.map((campaign) => (
                    <SelectItem key={campaign.id} value={String(campaign.id)}>
                      {campaign.title_en}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </section>
      )}

      <section className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="description">Description</Label>
          <Input
            id="description"
            value={form.data.description}
            onChange={(event) => form.setData('description', event.target.value)}
          />
          <InputError message={form.errors.description} />
        </div>
        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="notes">Notes</Label>
          <Textarea
            id="notes"
            value={form.data.notes}
            onChange={(event) => form.setData('notes', event.target.value)}
          />
          <InputError message={form.errors.notes} />
        </div>
      </section>

      <section className="space-y-4 rounded-lg border p-4">
        <div>
          <h3 className="font-semibold">Documents</h3>
          <p className="text-sm text-muted-foreground">
            Upload receipts, invoices, or PDFs (jpeg, png, pdf).
          </p>
        </div>

        {existingDocuments.length > 0 && (
          <ul className="space-y-2">
            {existingDocuments.map((document) => (
              <li
                key={document.id}
                className="flex items-center justify-between gap-2 text-sm"
              >
                <a
                  href={document.url}
                  target="_blank"
                  rel="noreferrer"
                  className="underline"
                >
                  {document.name}
                </a>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() =>
                    setRemoveDocumentIds((ids) => [...ids, document.id])
                  }
                >
                  Remove
                </Button>
              </li>
            ))}
          </ul>
        )}

        <Input
          type="file"
          multiple
          accept=".jpg,.jpeg,.png,.pdf"
          onChange={(event) =>
            form.setData(
              'documents',
              event.target.files ? Array.from(event.target.files) : [],
            )
          }
        />
        <InputError message={form.errors.documents} />
      </section>

      <div className="flex justify-end gap-2">
        <Button type="submit" disabled={form.processing}>
          {submitLabel}
        </Button>
      </div>
    </form>
  )
}
