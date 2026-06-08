import { useRef, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Search, X } from "lucide-react";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { NewsCard, type NewsItem } from "@/components/site/news-card";
import { NewsPagination } from "@/components/site/news-pagination";
import type { Paginated } from "@/types/pagination";

interface CampaignCategory {
  id: number;
  name_ar: string;
  name_en: string;
}

type SearchParams = {
  query?: string;
  category?: string;
  page?: number;
};

type PageProps = {
  campaigns: Paginated<NewsItem>;
  categories: CampaignCategory[];
  search: SearchParams;
};

function CategorySidebar({
  categories,
  activeCategory,
  allLabel,
  categoriesLabel,
  dir,
  onSelect,
}: {
  categories: CampaignCategory[];
  activeCategory: string | undefined;
  allLabel: string;
  categoriesLabel: string;
  dir: "ltr" | "rtl";
  onSelect: (id?: string) => void;
}) {
  const locale = dir === "rtl" ? "ar" : "en";

  return (
    <aside className="w-full md:w-56 shrink-0">
      <p className="mb-4 text-xs font-semibold uppercase tracking-widest text-gold">
        {categoriesLabel}
      </p>
      <ul className="space-y-1">
        <li>
          <button
            onClick={() => onSelect(undefined)}
            className={`w-full rounded-lg px-4 py-2.5 text-start text-sm font-medium transition-colors ${
              !activeCategory
                ? "bg-action-red text-white"
                : "text-ink hover:bg-surface-soft"
            }`}
          >
            {allLabel}
          </button>
        </li>
        {categories.map((cat) => (
          <li key={cat.id}>
            <button
              onClick={() => onSelect(String(cat.id))}
              className={`w-full rounded-lg px-4 py-2.5 text-start text-sm font-medium transition-colors ${
                activeCategory === String(cat.id)
                  ? "bg-action-red text-white"
                  : "text-ink hover:bg-surface-soft"
              }`}
            >
              {locale === "ar" ? cat.name_ar : cat.name_en}
            </button>
          </li>
        ))}
      </ul>
    </aside>
  );
}

export default function CampaignsIndex() {
  const { t, locale, dir } = useLocale();
  const { campaigns, categories, search } = usePage<PageProps>().props;

  const [queryInput, setQueryInput] = useState(search.query ?? "");
  const searchRef = useRef<HTMLInputElement>(null);

  const navigateTo = (patch: Partial<SearchParams>) => {
    router.get(
      "/campaigns",
      { ...search, ...patch, page: 1 } as Record<string, unknown>,
      { preserveScroll: true }
    );
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    navigateTo({ query: queryInput || undefined });
  };

  const handleClearSearch = () => {
    setQueryInput("");
    navigateTo({ query: undefined });
  };

  const handleCategorySelect = (id?: string) => {
    navigateTo({ category: id });
  };

  return (
    <>
      <Head title={t.campaignsPage.pageTitle} />

      <section className="bg-ink pt-32 pb-16 text-white md:pt-40 md:pb-20">
        <div className="mx-auto max-w-[1200px] px-6">
          <span className="mb-4 block text-xs font-semibold uppercase tracking-[0.2em] text-gold">
            {t.campaigns.eyebrow}
          </span>
          <h1 className="font-display text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">
            {t.campaignsPage.pageTitle}
          </h1>
          <p className="mt-4 max-w-xl text-lg text-white/75">
            {t.campaignsPage.pageIntro}
          </p>
        </div>
      </section>

      <div className="border-b border-black/5 bg-white shadow-sm">
        <div className="mx-auto max-w-[1200px] px-6 py-4">
          <form onSubmit={handleSearchSubmit} className="flex gap-3">
            <div className="relative flex-1">
              <Search
                className="pointer-events-none absolute top-1/2 -translate-y-1/2 h-4 w-4 text-body-text/40 ltr:left-3 rtl:right-3"
                aria-hidden="true"
              />
              <input
                ref={searchRef}
                type="search"
                value={queryInput}
                onChange={(e) => setQueryInput(e.target.value)}
                placeholder={t.campaignsPage.searchPlaceholder}
                dir={dir}
                className="h-11 w-full rounded-lg border border-black/10 bg-surface-soft py-2 text-sm text-ink placeholder:text-body-text/40 outline-none transition focus:border-action-red focus:ring-2 focus:ring-action-red/20 ltr:pl-9 ltr:pr-4 rtl:pr-9 rtl:pl-4"
              />
              {queryInput && (
                <button
                  type="button"
                  onClick={handleClearSearch}
                  aria-label="Clear search"
                  className="absolute top-1/2 -translate-y-1/2 text-body-text/40 hover:text-ink ltr:right-3 rtl:left-3"
                >
                  <X className="h-4 w-4" />
                </button>
              )}
            </div>
            <button
              type="submit"
              className="shrink-0 rounded-lg bg-action-red px-5 py-2 text-sm font-semibold text-white transition hover:bg-action-red/90 focus:outline-none focus:ring-2 focus:ring-action-red/40"
            >
              {locale === "ar" ? "بحث" : "Search"}
            </button>
          </form>
        </div>
      </div>

      <div className="bg-surface-soft py-12 md:py-16">
        <div className="mx-auto max-w-[1200px] px-6">
          <div className="flex flex-col gap-10 md:flex-row md:items-start md:gap-12">
            <CategorySidebar
              categories={categories}
              activeCategory={search.category}
              allLabel={t.campaignsPage.allCategories}
              categoriesLabel={t.campaignsPage.categories}
              dir={dir}
              onSelect={handleCategorySelect}
            />

            <div className="min-w-0 flex-1">
              {campaigns.data.length > 0 ? (
                <>
                  <p className="mb-6 text-sm text-body-text/60">
                    {campaigns.total}{" "}
                    {locale === "ar" ? "حملة" : "campaigns"}
                  </p>
                  <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {campaigns.data.map((item) => (
                      <NewsCard
                        key={item.id}
                        item={item}
                        readLabel={t.campaigns.readCampaign}
                        hrefBase="/campaigns"
                      />
                    ))}
                  </div>
                  <NewsPagination
                    currentPage={campaigns.current_page}
                    lastPage={campaigns.last_page}
                    search={search as Record<string, unknown>}
                    indexUrl="/campaigns"
                    dir={dir}
                  />
                </>
              ) : (
                <div className="flex flex-col items-center justify-center py-24 text-center">
                  <p className="text-lg font-medium text-body-text/60">
                    {t.campaignsPage.noResults}
                  </p>
                  {(search.query || search.category) && (
                    <button
                      onClick={() => {
                        setQueryInput("");
                        navigateTo({ query: undefined, category: undefined });
                      }}
                      className="mt-4 text-sm font-semibold text-action-red hover:underline"
                    >
                      {t.campaignsPage.allCategories}
                    </button>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

CampaignsIndex.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
