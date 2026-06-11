import { PaymentMethodsActionDialog } from './payment-methods-action-dialog'
import { PaymentMethodsDeleteDialog } from './payment-methods-delete-dialog'
import { usePaymentMethods } from './payment-methods-provider'

export function PaymentMethodsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = usePaymentMethods()

  return (
    <>
      <PaymentMethodsActionDialog
        key="payment-method-add"
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <PaymentMethodsActionDialog
            key={`payment-method-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <PaymentMethodsDeleteDialog
            key={`payment-method-delete-${currentRow.id}`}
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
