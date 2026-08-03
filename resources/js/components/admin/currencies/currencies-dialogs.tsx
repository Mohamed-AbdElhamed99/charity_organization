import { CurrenciesActionDialog } from './currencies-action-dialog'
import { CurrenciesDeleteDialog } from './currencies-delete-dialog'
import { useCurrencies } from './currencies-provider'

export function CurrenciesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useCurrencies()

  return (
    <>
      <CurrenciesActionDialog
        key="currency-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <CurrenciesActionDialog
            key={`currency-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <CurrenciesDeleteDialog
            key={`currency-delete-${currentRow.id}`}
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
