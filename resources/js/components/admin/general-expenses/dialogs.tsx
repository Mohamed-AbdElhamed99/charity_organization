import { GeneralExpensesActionDialog } from './action-dialog'
import { GeneralExpensesEditDialog } from './edit-dialog'
import { useGeneralExpenses } from './provider'
import { GeneralExpensesReverseDialog } from './reverse-dialog'
import type {
  GeneralExpenseAccountOption,
  GeneralExpenseCategoryOption,
  GeneralExpensePaymentMethodOption,
} from '@/types/models/general-expense'

type GeneralExpensesDialogsProps = {
  categories: GeneralExpenseCategoryOption[]
  accounts: GeneralExpenseAccountOption[]
  paymentMethods: GeneralExpensePaymentMethodOption[]
}

export function GeneralExpensesDialogs({
  categories,
  accounts,
  paymentMethods,
}: GeneralExpensesDialogsProps) {
  const { open, setOpen, currentRow, setCurrentRow } = useGeneralExpenses()

  return (
    <>
      <GeneralExpensesActionDialog
        key="general-expense-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
        categories={categories}
        accounts={accounts}
        paymentMethods={paymentMethods}
      />

      {currentRow && (
        <>
          <GeneralExpensesEditDialog
            key={`general-expense-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
            categories={categories}
          />

          <GeneralExpensesReverseDialog
            key={`general-expense-reverse-${currentRow.id}`}
            open={open === 'reverse'}
            onOpenChange={() => {
              setOpen('reverse')
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
