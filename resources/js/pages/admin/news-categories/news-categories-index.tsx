import { Head, usePage } from '@inertiajs/react'
import { Main } from '@/components/layout/main'
import { NewsCategoriesDialogs } from '@/components/admin/news-categories/news-categories-dialogs'
import { NewsCategoriesPrimaryButtons } from '@/components/admin/news-categories/news-categories-primary-buttons'
import { NewsCategoriesProvider } from '@/components/admin/news-categories/news-categories-provider'
import { NewsCategoriesTable } from '@/components/admin/news-categories/news-categories-table'
import { index as newsCategoriesIndex } from '@/routes/admin/news-categories'
import { type NewsCategory } from '@/types/models/news-category'
import { type Paginated } from '@/types/pagination'

type SearchParams = {
  query?: string
  status?: string | string[]
  page?: number
  per_page?: number
}

type PageProps = {
  newsCategories: Paginated<NewsCategory>
  search: SearchParams
}

export default function NewsCategoriesIndex() {
  const { newsCategories, search } = usePage<PageProps>().props

  return (
    <>
      <Head title="News Categories" />

      <NewsCategoriesProvider>
        <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
          <div className="flex flex-wrap items-end justify-between gap-2">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">
                News Categories
              </h2>
              <p className="text-muted-foreground">
                Manage categories for news articles.
              </p>
            </div>
            <NewsCategoriesPrimaryButtons />
          </div>

          <NewsCategoriesTable
            newsCategories={newsCategories}
            search={search}
          />
        </Main>

        <NewsCategoriesDialogs />
      </NewsCategoriesProvider>
    </>
  )
}

NewsCategoriesIndex.layout = {
  breadcrumbs: [
    {
      title: 'News Categories',
      href: newsCategoriesIndex.url(),
    },
  ],
}
