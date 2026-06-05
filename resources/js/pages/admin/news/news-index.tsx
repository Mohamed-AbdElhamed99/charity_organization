import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { NewsDialogs } from '@/components/admin/news/news-dialogs'
import { NewsPrimaryButtons } from '@/components/admin/news/news-primary-buttons'
import { NewsProvider } from '@/components/admin/news/news-provider'
import { NewsTable } from '@/components/admin/news/news-table'
import { index as newsIndex } from '@/routes/admin/news'
import type { News, NewsCategory } from '@/types/models/news'
import type { Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  category?: string | string[]
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  news: Paginated<News>
  categories: NewsCategory[]
  search: SearchParams
}

export default function NewsIndex() {
  const { news, categories, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="News" />

      <NewsProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">News</h2>
              <p className="text-muted-foreground">
                Manage news articles and media content.
              </p>
            </div>
            <NewsPrimaryButtons />
          </div>

          <NewsTable news={news} categories={categories} search={search} />
        </Main>

        <NewsDialogs categories={categories} />
      </NewsProvider>
    </>
  )
}

NewsIndex.layout = {
  breadcrumbs: [
    {
      title: 'News',
      href: newsIndex.url(),
    },
  ],
}
