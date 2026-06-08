import type {
  AccountOption,
  CurrencyOption,
  PaymentMethodOption,
} from '@/types/models/transaction'
import { TransactionsActionDialog } from './transactions-action-dialog'
import { useTransactions } from './transactions-provider'

type TransactionsDialogsProps = {
  accounts: AccountOption[]
  currencies: CurrencyOption[]
  paymentMethods: PaymentMethodOption[]
}

export function TransactionsDialogs({
  accounts,
  currencies,
  paymentMethods,
}: TransactionsDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useTransactions()

  return (
    <>
      <TransactionsActionDialog
        key="transaction-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        accounts={accounts}
        currencies={currencies}
        paymentMethods={paymentMethods}
      />

      {currentRow && (
        <TransactionsActionDialog
          key={`transaction-edit-${currentRow.id}`}
          open={open === 'edit'}
          onOpenChange={() => {
            setOpen('edit')
            setTimeout(() => {
              setCurrentRow(null)
            }, 500)
          }}
          currentRow={currentRow}
          accounts={accounts}
          currencies={currencies}
          paymentMethods={paymentMethods}
        />
      )}
    </>
  )
}
