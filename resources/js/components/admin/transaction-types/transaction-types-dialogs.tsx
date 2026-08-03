import { TransactionTypeActionDialog } from './transaction-type-action-dialog'
import { TransactionTypesDeleteDialog } from './transaction-types-delete-dialog'
import { useTransactionTypes } from './transaction-types-provider'

export function TransactionTypesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useTransactionTypes()

  return (
    <>
      <TransactionTypeActionDialog
        key="transaction-type-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <TransactionTypeActionDialog
            key={`transaction-type-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <TransactionTypesDeleteDialog
            key={`transaction-type-delete-${currentRow.id}`}
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
