import { type AccountTypeOption, type CurrencyOption } from '@/types/models/account'
import { AccountsActionDialog } from './accounts-action-dialog'
import { AccountsDeleteDialog } from './accounts-delete-dialog'
import { useAccounts } from './accounts-provider'

type AccountsDialogsProps = {
  currencies: CurrencyOption[]
  accountTypes: AccountTypeOption[]
}

export function AccountsDialogs({ currencies, accountTypes }: AccountsDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useAccounts()

  return (
    <>
      <AccountsActionDialog
        key="account-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        currencies={currencies}
        accountTypes={accountTypes}
      />

      {currentRow && (
        <>
          <AccountsActionDialog
            key={`account-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
            currencies={currencies}
            accountTypes={accountTypes}
          />

          <AccountsDeleteDialog
            key={`account-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />
        </>
      )}
    </>
  )
}
