import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { ContactMessagesTable } from '@/components/admin/contact-messages/contact-messages-table'
import { index as contactMessagesIndex } from '@/routes/admin/contact-messages'
import { type ContactMessage } from '@/types/models/contact-message'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  messages: Paginated<ContactMessage>
  search: SearchParams
}

export default function ContactMessagesIndex() {
  const { messages, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="Contact Messages" />

      <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Contact Messages</h2>
          <p className="text-muted-foreground">
            Review submissions from the public contact form.
          </p>
        </div>

        <ContactMessagesTable messages={messages} search={search} />
      </Main>
    </>
  )
}

ContactMessagesIndex.layout = {
  breadcrumbs: [
    {
      title: 'Contact Messages',
      href: contactMessagesIndex.url(),
    },
  ],
}
