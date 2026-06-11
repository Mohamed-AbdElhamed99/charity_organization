import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { PaymentMethodsDialogs } from '@/components/admin/payment-methods/payment-methods-dialogs'
import { PaymentMethodsPrimaryButtons } from '@/components/admin/payment-methods/payment-methods-primary-buttons'
import { PaymentMethodsProvider } from '@/components/admin/payment-methods/payment-methods-provider'
import { PaymentMethodsTable } from '@/components/admin/payment-methods/payment-methods-table'
import { index as paymentMethodsIndex } from '@/routes/admin/payment-methods'
import { type PaymentMethod } from '@/types/models/payment-method'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  paymentMethods: Paginated<PaymentMethod>
  search: SearchParams
}

export default function PaymentMethodsIndex() {
  const { paymentMethods, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Payment Methods" />

      <PaymentMethodsProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                Payment Methods
              </h2>
              <p className="text-muted-foreground">
                Manage how money moves through the organization.
              </p>
            </div>
            <PaymentMethodsPrimaryButtons />
          </div>

          <PaymentMethodsTable paymentMethods={paymentMethods} search={search} />
        </Main>

        <PaymentMethodsDialogs />
      </PaymentMethodsProvider>
    </>
  )
}

PaymentMethodsIndex.layout = {
  breadcrumbs: [
    {
      title: 'Payment Methods',
      href: paymentMethodsIndex.url(),
    },
  ],
}
