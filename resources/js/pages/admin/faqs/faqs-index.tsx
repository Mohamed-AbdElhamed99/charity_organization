import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { FaqsDialogs } from '@/components/admin/faqs/faqs-dialogs'
import { FaqsPrimaryButtons } from '@/components/admin/faqs/faqs-primary-buttons'
import { FaqsProvider } from '@/components/admin/faqs/faqs-provider'
import { FaqsTable } from '@/components/admin/faqs/faqs-table'
import { index as faqsIndex } from '@/routes/admin/faqs'
import { type Faq } from '@/types/models/faq'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  faqs: Paginated<Faq>
  search: SearchParams
}

export default function FaqsIndex() {
  const { faqs, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="FAQs" />

      <FaqsProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">FAQs</h2>
              <p className="text-muted-foreground">
                Manage frequently asked questions for the public site.
              </p>
            </div>
            <FaqsPrimaryButtons />
          </div>

          <FaqsTable faqs={faqs} search={search} />
        </Main>

        <FaqsDialogs />
      </FaqsProvider>
    </>
  )
}

FaqsIndex.layout = {
  breadcrumbs: [
    {
      title: 'FAQs',
      href: faqsIndex.url(),
    },
  ],
}
