import { useMemo, useState } from 'react'
import { useForm, usePage } from '@inertiajs/react'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import InputError from '@/components/input-error'
import { PaymentMethodsActionDialog } from '@/components/admin/payment-methods/payment-methods-action-dialog'
import { CurrenciesActionDialog } from '@/components/admin/currencies/currencies-action-dialog'
import { TransactionTypeActionDialog } from '@/components/admin/transaction-types/transaction-type-action-dialog'
import { SearchableSelect } from '@/components/shared/searchable-select'
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
  GeneralExpenseOption,
  MeetingOption,
  PaymentMethodOption,
  SelectOption,
  Transaction,
  UserOption,
} from '@/types/models/transaction'
import type { PaymentMethod } from '@/types/models/payment-method'
import type { Currency } from '@/types/models/currency'
import type {
  TransactionType,
  TransactionTypeOption,
} from '@/types/models/transaction-type'

type TransactionFormProps = {
  transaction?: Transaction
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
  campaigns: CampaignOption[]
  users: UserOption[]
  beneficiaries: BeneficiaryOption[]
  meetings: MeetingOption[]
  generalExpenses: GeneralExpenseOption[]
  transactionTypes: TransactionTypeOption[]
  directions: SelectOption[]
  defaultTypeId?: number | null
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

function formatBalanceInput(
  value: number | string | null | undefined,
): string {
  if (value === null || value === undefined || value === '') {
    return ''
  }

  const numeric = typeof value === 'string' ? Number(value) : value

  if (Number.isNaN(numeric)) {
    return ''
  }

  return numeric.toFixed(2)
}

function computeBalanceAfter(
  balanceBefore: string,
  amount: number,
  direction: string,
): string {
  const before = Number(balanceBefore)

  if (Number.isNaN(before) || Number.isNaN(amount)) {
    return ''
  }

  const after = direction === 'out' ? before - amount : before + amount

  return after.toFixed(2)
}

export function TransactionForm({
  transaction,
  accounts,
  currencies,
  paymentMethods: initialPaymentMethods,
  campaigns,
  users,
  beneficiaries,
  meetings,
  generalExpenses,
  transactionTypes: initialTransactionTypes,
  directions,
  defaultTypeId,
  submitUrl,
  method,
  submitLabel,
}: TransactionFormProps) {
  const { auth } = usePage<{
    auth: { permissions: string[] }
  }>().props
  const permissions = auth.permissions ?? []
  const canCreateType = permissions.includes('create_transaction_types')
  const canEditType = permissions.includes('edit_transaction_types')
  const canCreatePaymentMethod = permissions.includes('create_payment_methods')
  const canEditPaymentMethod = permissions.includes('edit_payment_methods')
  const canCreateCurrency = permissions.includes('create_currencies')
  const canEditCurrency = permissions.includes('edit_currencies')

  const [removeDocumentIds, setRemoveDocumentIds] = useState<number[]>([])
  const [typeOptions, setTypeOptions] = useState<TransactionTypeOption[]>(
    initialTransactionTypes,
  )
  const [typeDialogOpen, setTypeDialogOpen] = useState(false)
  const [editingType, setEditingType] = useState<TransactionType | null>(null)
  const [paymentMethodOptions, setPaymentMethodOptions] = useState<
    PaymentMethodOption[]
  >(initialPaymentMethods)
  const [paymentMethodDialogOpen, setPaymentMethodDialogOpen] = useState(false)
  const [editingPaymentMethod, setEditingPaymentMethod] =
    useState<PaymentMethod | null>(null)
  const [currencyOptions, setCurrencyOptions] =
    useState<CurrencyOption[]>(currencies)
  const [currencyDialogOpen, setCurrencyDialogOpen] = useState(false)
  const [editingCurrency, setEditingCurrency] = useState<Currency | null>(null)

  const initialTypeId =
    transaction?.transaction_type_id ??
    defaultTypeId ??
    initialTransactionTypes.find((type) => type.code === 'donation')?.id ??
    initialTransactionTypes[0]?.id ??
    null

  const form = useForm({
    account_id: transaction ? String(transaction.account_id) : '',
    transaction_type_id: initialTypeId ? String(initialTypeId) : '',
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
    currency_equivalents: (transaction?.currency_equivalents ?? []).map(
      (row) => ({
        currency_code: row.currency_code,
        equivalent: String(row.equivalent),
      }),
    ),
    balance_before: formatBalanceInput(
      transaction?.balance_before ??
        accounts.find((account) => account.id === transaction?.account_id)
          ?.current_balance,
    ),
    running_balance: formatBalanceInput(transaction?.running_balance),
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
      transfer_date:
        transaction?.transfer?.transfer_date ??
        transaction?.transaction_date ??
        todayDateInputValue(),
      notes: transaction?.transfer?.notes ?? '',
    },
    beneficiary_ids: (transaction?.beneficiary_ids ?? []).map(String),
    campaign_ids: (transaction?.campaign_ids ?? []).map(String),
    meeting_ids: (transaction?.meeting_ids ?? []).map(String),
    general_expense_ids: (transaction?.general_expense_ids ?? []).map(String),
  })

  const [balanceAfterTouched, setBalanceAfterTouched] = useState(
    Boolean(transaction?.running_balance),
  )

  const selectedType = useMemo(
    () =>
      typeOptions.find(
        (type) => String(type.id) === form.data.transaction_type_id,
      ),
    [typeOptions, form.data.transaction_type_id],
  )

  const selectedAccount = useMemo(
    () => accounts.find((account) => String(account.id) === form.data.account_id),
    [accounts, form.data.account_id],
  )

  const accountCurrency = useMemo(
    () =>
      currencyOptions.find(
        (currency) => currency.id === selectedAccount?.currency_id,
      ),
    [currencyOptions, selectedAccount],
  )

  const originalCurrency = useMemo(
    () =>
      currencyOptions.find(
        (currency) => String(currency.id) === form.data.original_currency_id,
      ) ?? accountCurrency,
    [currencyOptions, form.data.original_currency_id, accountCurrency],
  )

  const selectedOriginalCurrencyOption = useMemo(
    () =>
      currencyOptions.find(
        (currency) => String(currency.id) === form.data.original_currency_id,
      ),
    [currencyOptions, form.data.original_currency_id],
  )

  const isTransfer = selectedType?.code === 'transfer'
  const needsFx =
    !!form.data.original_currency_id &&
    !!selectedAccount?.currency_id &&
    String(selectedAccount.currency_id) !== form.data.original_currency_id

  const equivalentCurrencyOptions = useMemo(
    () =>
      currencyOptions.filter(
        (currency) => currency.code !== accountCurrency?.code,
      ),
    [currencyOptions, accountCurrency],
  )

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

  const syncBalanceAfter = (
    balanceBefore: string,
    amount: number,
    direction: string,
  ) => {
    if (balanceAfterTouched) {
      return
    }

    form.setData(
      'running_balance',
      computeBalanceAfter(balanceBefore, amount, direction),
    )
  }

  const existingDocuments = (transaction?.documents ?? []).filter(
    (document) => !removeDocumentIds.includes(document.id),
  )

  const upsertTypeOption = (type: TransactionType) => {
    setTypeOptions((current) => {
      const option = { id: type.id, name: type.name, code: type.code }
      const exists = current.some((item) => item.id === type.id)

      if (!exists) {
        return [...current, option].sort((a, b) => a.name.localeCompare(b.name))
      }

      return current
        .map((item) => (item.id === type.id ? option : item))
        .sort((a, b) => a.name.localeCompare(b.name))
    })
  }

  const handleTypeSuccess = (type: TransactionType) => {
    upsertTypeOption(type)
    form.setData('transaction_type_id', String(type.id))

    if (type.code === 'transfer') {
      form.setData('direction', 'out')
    }

    setEditingType(null)
  }

  const selectedPaymentMethod = useMemo(
    () =>
      paymentMethodOptions.find(
        (method) => String(method.id) === form.data.payment_method_id,
      ),
    [paymentMethodOptions, form.data.payment_method_id],
  )

  const upsertPaymentMethodOption = (method: PaymentMethod) => {
    setPaymentMethodOptions((current) => {
      const option = { id: method.id, name: method.name, code: method.code }
      const exists = current.some((item) => item.id === method.id)

      if (!exists) {
        return [...current, option].sort((a, b) => a.name.localeCompare(b.name))
      }

      return current
        .map((item) => (item.id === method.id ? option : item))
        .sort((a, b) => a.name.localeCompare(b.name))
    })
  }

  const handlePaymentMethodSuccess = (method: PaymentMethod) => {
    upsertPaymentMethodOption(method)
    form.setData('payment_method_id', String(method.id))
    setEditingPaymentMethod(null)
  }

  const upsertCurrencyOption = (currency: Currency) => {
    setCurrencyOptions((current) => {
      const option = {
        id: currency.id,
        code: currency.code,
        symbol: currency.symbol ?? currency.code,
        name: currency.name,
      }
      const exists = current.some((item) => item.id === currency.id)

      if (!exists) {
        return [...current, option].sort((a, b) => a.code.localeCompare(b.code))
      }

      return current
        .map((item) => (item.id === currency.id ? option : item))
        .sort((a, b) => a.code.localeCompare(b.code))
    })
  }

  const handleCurrencySuccess = (currency: Currency) => {
    upsertCurrencyOption(currency)
    form.setData('original_currency_id', String(currency.id))
    setEditingCurrency(null)
  }

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const payloadDirection = isTransfer ? 'out' : form.data.direction
    const ledgerGross = needsFx
      ? String(convertedPreview)
      : form.data.original_amount || form.data.gross_amount

    form.transform((data) => {
      const currencyEquivalents = (data.currency_equivalents ?? [])
        .filter(
          (row) =>
            row.currency_code.trim() !== '' &&
            row.equivalent.trim() !== '' &&
            Number(row.equivalent) > 0,
        )
        .map((row) => ({
          currency_code: row.currency_code,
          equivalent: row.equivalent,
        }))

      return {
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
        currency_equivalents: currencyEquivalents,
        balance_before: data.balance_before || null,
        running_balance: data.running_balance || null,
        remove_document_ids: removeDocumentIds,
        transfer: isTransfer
          ? {
              recipient_kind: data.transfer.recipient_kind,
              recipient_id: data.transfer.recipient_id || null,
              recipient_label: data.transfer.recipient_label,
              recipient_phone: data.transfer.recipient_phone,
              purpose: data.transfer.purpose,
              transfer_date: data.transfer.transfer_date,
              notes: data.transfer.notes,
            }
          : undefined,
        beneficiary_ids: data.beneficiary_ids.map((id) => Number(id)),
        campaign_ids: data.campaign_ids.map((id) => Number(id)),
        meeting_ids: data.meeting_ids.map((id) => Number(id)),
        general_expense_ids: data.general_expense_ids.map((id) => Number(id)),
        payment_method_id: data.payment_method_id || null,
      }
    })

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
    <>
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

              if (!transaction) {
                const before = formatBalanceInput(
                  account?.current_balance ?? account?.opening_balance,
                )
                form.setData('balance_before', before)
                setBalanceAfterTouched(false)
                syncBalanceAfter(
                  before,
                  Number(form.data.original_amount || form.data.gross_amount || 0),
                  isTransfer ? 'out' : form.data.direction,
                )
              }
            }}
          >
            <SelectTrigger id="account_id" className="w-full">
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
          <div className="flex items-center justify-between gap-2">
            <Label htmlFor="transaction_type_id">Type</Label>
            <div className="flex items-center gap-1">
              {canEditType && selectedType ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 px-2"
                  onClick={() => {
                    setEditingType({
                      id: selectedType.id,
                      name: selectedType.name,
                      code: selectedType.code,
                      description: null,
                      is_active: true,
                      is_system: Boolean(selectedType.is_system),
                      created_at: '',
                    })
                    setTypeDialogOpen(true)
                  }}
                >
                  <Pencil className="size-3.5" />
                  <span className="sr-only">Edit type</span>
                </Button>
              ) : null}
              {canCreateType ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 px-2"
                  onClick={() => {
                    setEditingType(null)
                    setTypeDialogOpen(true)
                  }}
                >
                  <Plus className="size-3.5" />
                  <span>Add type</span>
                </Button>
              ) : null}
            </div>
          </div>
          <Select
            value={form.data.transaction_type_id}
            onValueChange={(value) => {
              form.setData('transaction_type_id', value)
              const nextType = typeOptions.find(
                (type) => String(type.id) === value,
              )
              const nextDirection =
                nextType?.code === 'transfer' ? 'out' : form.data.direction
              if (nextType?.code === 'transfer') {
                form.setData('direction', 'out')
              }
              syncBalanceAfter(
                form.data.balance_before,
                Number(form.data.original_amount || form.data.gross_amount || 0),
                nextDirection,
              )
            }}
          >
            <SelectTrigger id="transaction_type_id" className="w-full">
              <SelectValue placeholder="Select type" />
            </SelectTrigger>
            <SelectContent>
              {typeOptions.map((type) => (
                <SelectItem key={type.id} value={String(type.id)}>
                  {type.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.transaction_type_id} />
        </div>

        {!isTransfer && (
          <div className="space-y-2">
            <Label htmlFor="direction">Direction</Label>
            <Select
            value={form.data.direction}
            onValueChange={(value) => {
              form.setData('direction', value)
              syncBalanceAfter(
                form.data.balance_before,
                Number(form.data.original_amount || form.data.gross_amount || 0),
                value,
              )
            }}
          >
              <SelectTrigger id="direction" className="w-full">
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
          <div className="flex items-center justify-between gap-2">
            <Label htmlFor="payment_method_id">Payment method</Label>
            <div className="flex items-center gap-1">
              {canEditPaymentMethod && selectedPaymentMethod ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 px-2"
                  onClick={() => {
                    setEditingPaymentMethod({
                      id: selectedPaymentMethod.id,
                      name: selectedPaymentMethod.name,
                      code: selectedPaymentMethod.code,
                      is_active: true,
                      created_at: '',
                    })
                    setPaymentMethodDialogOpen(true)
                  }}
                >
                  <Pencil className="size-3.5" />
                  <span className="sr-only">Edit payment method</span>
                </Button>
              ) : null}
              {canCreatePaymentMethod ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 px-2"
                  onClick={() => {
                    setEditingPaymentMethod(null)
                    setPaymentMethodDialogOpen(true)
                  }}
                >
                  <Plus className="size-3.5" />
                  <span>Add method</span>
                </Button>
              ) : null}
            </div>
          </div>
          <Select
            value={form.data.payment_method_id || '__none'}
            onValueChange={(value) =>
              form.setData(
                'payment_method_id',
                value === '__none' ? '' : value,
              )
            }
          >
            <SelectTrigger id="payment_method_id" className="w-full">
              <SelectValue placeholder="Optional" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none">None</SelectItem>
              {paymentMethodOptions.map((method) => (
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
            <div className="flex items-center justify-between gap-2">
              <Label htmlFor="original_currency_id">Original currency</Label>
              <div className="flex items-center gap-1">
                {canEditCurrency && selectedOriginalCurrencyOption ? (
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2"
                    onClick={() => {
                      setEditingCurrency({
                        id: selectedOriginalCurrencyOption.id,
                        code: selectedOriginalCurrencyOption.code,
                        name:
                          selectedOriginalCurrencyOption.name ??
                          selectedOriginalCurrencyOption.code,
                        symbol: selectedOriginalCurrencyOption.symbol,
                        is_active: true,
                        is_default: false,
                        created_at: '',
                      })
                      setCurrencyDialogOpen(true)
                    }}
                  >
                    <Pencil className="size-3.5" />
                    <span className="sr-only">Edit currency</span>
                  </Button>
                ) : null}
                {canCreateCurrency ? (
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 px-2"
                    onClick={() => {
                      setEditingCurrency(null)
                      setCurrencyDialogOpen(true)
                    }}
                  >
                    <Plus className="size-3.5" />
                    <span>Add currency</span>
                  </Button>
                ) : null}
              </div>
            </div>
            <Select
              value={
                form.data.original_currency_id ||
                (accountCurrency ? String(accountCurrency.id) : '')
              }
              onValueChange={(value) =>
                form.setData('original_currency_id', value)
              }
            >
              <SelectTrigger id="original_currency_id" className="w-full">
                <SelectValue placeholder="Select currency" />
              </SelectTrigger>
              <SelectContent>
                {currencyOptions.map((currency) => (
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
                syncBalanceAfter(
                  form.data.balance_before,
                  Number(event.target.value || 0),
                  isTransfer ? 'out' : form.data.direction,
                )
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
                onChange={(event) => {
                  form.setData('exchange_rate', event.target.value)
                  const original = Number(
                    form.data.original_amount || form.data.gross_amount || 0,
                  )
                  const rate = Number(event.target.value || 1)
                  const converted = Math.round(original * rate * 100) / 100
                  syncBalanceAfter(
                    form.data.balance_before,
                    converted,
                    isTransfer ? 'out' : form.data.direction,
                  )
                }}
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

          <div className="space-y-2">
            <Label htmlFor="balance_before">
              Balance before ({accountCurrency?.code ?? 'account'})
            </Label>
            <Input
              id="balance_before"
              type="number"
              step="0.01"
              value={form.data.balance_before}
              onChange={(event) => {
                form.setData('balance_before', event.target.value)
                syncBalanceAfter(
                  event.target.value,
                  needsFx
                    ? convertedPreview
                    : Number(
                        form.data.original_amount || form.data.gross_amount || 0,
                      ),
                  isTransfer ? 'out' : form.data.direction,
                )
              }}
            />
            <InputError message={form.errors.balance_before} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="running_balance">
              Balance after ({accountCurrency?.code ?? 'account'})
            </Label>
            <Input
              id="running_balance"
              type="number"
              step="0.01"
              value={form.data.running_balance}
              onChange={(event) => {
                setBalanceAfterTouched(true)
                form.setData('running_balance', event.target.value)
              }}
            />
            <InputError message={form.errors.running_balance} />
            <p className="text-xs text-muted-foreground">
              Defaults from balance before ± amount. Edit to override.
            </p>
          </div>

          <div className="space-y-2 md:col-span-2">
            <Label>Ledger amount ({accountCurrency?.code ?? 'account'})</Label>
            <p className="text-lg font-semibold">
              {convertedPreview.toFixed(2)} {accountCurrency?.code ?? ''}
            </p>
          </div>
        </div>

        <div className="space-y-3 border-t pt-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h4 className="font-medium">Currency equivalents</h4>
              <p className="text-sm text-muted-foreground">
                Optional same-day rates: 1{' '}
                {accountCurrency?.code ?? 'ledger'} equals how much in another
                currency.
              </p>
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={equivalentCurrencyOptions.length === 0}
              onClick={() =>
                form.setData('currency_equivalents', [
                  ...form.data.currency_equivalents,
                  {
                    currency_code: equivalentCurrencyOptions[0]?.code ?? '',
                    equivalent: '',
                  },
                ])
              }
            >
              <Plus className="size-3.5" />
              Add currency
            </Button>
          </div>

          {form.data.currency_equivalents.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No equivalent currencies added.
            </p>
          ) : (
            <div className="space-y-3">
              {form.data.currency_equivalents.map((row, index) => (
                <div
                  key={`equivalent-${index}`}
                  className="grid gap-3 md:grid-cols-[1fr_1fr_auto]"
                >
                  <div className="space-y-2">
                    <Label htmlFor={`currency_equivalents_${index}_code`}>
                      Currency
                    </Label>
                    <Select
                      value={row.currency_code}
                      onValueChange={(value) => {
                        const next = [...form.data.currency_equivalents]
                        next[index] = { ...next[index], currency_code: value }
                        form.setData('currency_equivalents', next)
                      }}
                    >
                      <SelectTrigger
                        id={`currency_equivalents_${index}_code`}
                        className="w-full"
                      >
                        <SelectValue placeholder="Select currency" />
                      </SelectTrigger>
                      <SelectContent>
                        {equivalentCurrencyOptions.map((currency) => (
                          <SelectItem key={currency.id} value={currency.code}>
                            {currency.code} ({currency.symbol})
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <InputError
                      message={
                        form.errors[
                          `currency_equivalents.${index}.currency_code`
                        ]
                      }
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor={`currency_equivalents_${index}_equivalent`}>
                      1 {accountCurrency?.code ?? 'ledger'} =
                    </Label>
                    <Input
                      id={`currency_equivalents_${index}_equivalent`}
                      type="number"
                      step="0.00000001"
                      min="0"
                      value={row.equivalent}
                      onChange={(event) => {
                        const next = [...form.data.currency_equivalents]
                        next[index] = {
                          ...next[index],
                          equivalent: event.target.value,
                        }
                        form.setData('currency_equivalents', next)
                      }}
                    />
                    <InputError
                      message={
                        form.errors[`currency_equivalents.${index}.equivalent`]
                      }
                    />
                  </div>

                  <div className="flex items-end">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="h-9 px-2"
                      aria-label="Remove currency equivalent"
                      onClick={() =>
                        form.setData(
                          'currency_equivalents',
                          form.data.currency_equivalents.filter(
                            (_, rowIndex) => rowIndex !== index,
                          ),
                        )
                      }
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
          <InputError message={form.errors.currency_equivalents} />
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
                <SelectTrigger className="w-full">
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
                <SearchableSelect
                  mode="single"
                  options={users}
                  valueKey="id"
                  labelKey="name"
                  value={form.data.transfer.recipient_id || null}
                  onChange={(id) =>
                    form.setData('transfer', {
                      ...form.data.transfer,
                      recipient_id: id ?? '',
                    })
                  }
                  placeholder="Select user…"
                  searchPlaceholder="Search users…"
                  emptyText="No users found."
                  noneAvailableText="No users available."
                  error={form.errors['transfer.recipient_id']}
                />
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
                  <SelectTrigger className="w-full">
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
          </div>
        </section>
      )}

      <section className="space-y-4 rounded-lg border p-4">
        <div>
          <h3 className="font-semibold">Related records</h3>
          <p className="text-sm text-muted-foreground">
            Optionally link this transaction to beneficiaries, campaigns,
            meetings, or general expenses.
          </p>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label>Beneficiaries</Label>
            <SearchableSelect
              mode="multiple"
              options={beneficiaries}
              valueKey="id"
              labelKey="display_name"
              value={form.data.beneficiary_ids}
              onChange={(ids) => form.setData('beneficiary_ids', ids)}
              placeholder="Select beneficiaries…"
              searchPlaceholder="Search beneficiaries…"
              emptyText="No beneficiaries found."
              noneAvailableText="No beneficiaries available."
              error={form.errors.beneficiary_ids}
            />
          </div>

          <div className="space-y-2">
            <Label>Campaigns</Label>
            <SearchableSelect
              mode="multiple"
              options={campaigns}
              valueKey="id"
              labelKey="title_en"
              value={form.data.campaign_ids}
              onChange={(ids) => form.setData('campaign_ids', ids)}
              placeholder="Select campaigns…"
              searchPlaceholder="Search campaigns…"
              emptyText="No campaigns found."
              noneAvailableText="No campaigns available."
              error={form.errors.campaign_ids}
            />
          </div>

          <div className="space-y-2">
            <Label>Meetings</Label>
            <SearchableSelect
              mode="multiple"
              options={meetings}
              valueKey="id"
              labelKey="label"
              value={form.data.meeting_ids}
              onChange={(ids) => form.setData('meeting_ids', ids)}
              placeholder="Select meetings…"
              searchPlaceholder="Search meetings…"
              emptyText="No meetings found."
              noneAvailableText="No meetings available."
              error={form.errors.meeting_ids}
            />
          </div>

          <div className="space-y-2">
            <Label>General expenses</Label>
            <SearchableSelect
              mode="multiple"
              options={generalExpenses}
              valueKey="id"
              labelKey="name"
              value={form.data.general_expense_ids}
              onChange={(ids) => form.setData('general_expense_ids', ids)}
              placeholder="Select general expenses…"
              searchPlaceholder="Search general expenses…"
              emptyText="No general expenses found."
              noneAvailableText="No general expenses available."
              error={form.errors.general_expense_ids}
            />
          </div>
        </div>
      </section>

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

    <TransactionTypeActionDialog
      open={typeDialogOpen}
      onOpenChange={(open) => {
        setTypeDialogOpen(open)
        if (!open) {
          setEditingType(null)
        }
      }}
      currentRow={editingType}
      onSuccess={handleTypeSuccess}
    />

    <PaymentMethodsActionDialog
      open={paymentMethodDialogOpen}
      onOpenChange={(open) => {
        setPaymentMethodDialogOpen(open)
        if (!open) {
          setEditingPaymentMethod(null)
        }
      }}
      currentRow={editingPaymentMethod}
      onSuccess={handlePaymentMethodSuccess}
    />

    <CurrenciesActionDialog
      open={currencyDialogOpen}
      onOpenChange={(open) => {
        setCurrencyDialogOpen(open)
        if (!open) {
          setEditingCurrency(null)
        }
      }}
      currentRow={editingCurrency}
      onSuccess={handleCurrencySuccess}
    />
    </>
  )
}
