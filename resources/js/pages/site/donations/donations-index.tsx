import { useEffect, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Home } from "lucide-react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import {
  CategorySidebar,
  type CategoryItem,
} from "@/components/site/category-sidebar";
import { CampaignCard, type CampaignCardItem } from "@/components/site/campaign-card";
import { CampaignPagination } from "@/components/site/campaign-pagination";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import type { Paginated } from "@/types/pagination";
import type { FeeConfig } from "@/lib/donation-fee";

type Filters = {
  category?: string;
  search?: string;
  page?: number;
};

type PageProps = {
  campaigns: Paginated<CampaignCardItem>;
  categories: CategoryItem[];
  filters: Filters;
  feeConfig: FeeConfig;
};

function CampaignSkeletonGrid() {
  return (
    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {Array.from({ length: 6 }).map((_, index) => (
        <div key={index} className="rounded-2xl ring-1 ring-black/5 overflow-hidden">
          <Skeleton className="aspect-[4/3] w-full" />
          <div className="space-y-3 p-5">
            <Skeleton className="h-5 w-3/4 mx-auto" />
            <Skeleton className="h-10 w-32 mx-auto rounded-full" />
          </div>
        </div>
      ))}
    </div>
  );
}

export default function DonationsIndex() {
  const { t, locale, dir } = useLocale();
  const { campaigns, categories, filters } = usePage<PageProps>().props;
  const [loading, setLoading] = useState(false);
  const [searchInput, setSearchInput] = useState(filters.search ?? "");

  useEffect(() => {
    const start = router.on("start", (event) => {
      if (event.detail.visit.url.pathname.endsWith("/donations")) {
        setLoading(true);
      }
    });
    const finish = router.on("finish", () => setLoading(false));

    return () => {
      start();
      finish();
    };
  }, []);

  const resetFilters = () => {
    router.get(route("donations.index"), {}, {
      preserveScroll: true,
      only: ["campaigns", "filters"],
    });
    setSearchInput("");
  };

  const hasActiveFilters = Boolean(filters.search || filters.category);
  const countLabel =
    campaigns.total === 1
      ? t.donationsPage.campaignsCountOne
      : t.donationsPage.campaignsCount;

  return (
    <>
      <Head title={t.donationsPage.pageTitle} />

      <section className="bg-ink pt-32 pb-12 text-white md:pt-40 md:pb-16">
        <div className="mx-auto max-w-[1200px] px-6 text-center">
          <span className="mb-4 block text-xs font-semibold uppercase tracking-[0.2em] text-gold">
            {t.donationsPage.eyebrow}
          </span>
          <h1 className="font-display text-4xl font-bold leading-tight md:text-5xl">
            {t.donationsPage.pageTitle}
          </h1>
          <p className="mx-auto mt-4 max-w-xl text-lg text-white/75">
            {t.donationsPage.pageIntro}
          </p>
          <Breadcrumb className="mt-6 flex justify-center">
            <BreadcrumbList className="text-white/70">
              <BreadcrumbItem>
                <BreadcrumbLink
                  href={route("home")}
                  className="inline-flex items-center gap-1 text-white/80 hover:text-white"
                >
                  <Home className="size-4" aria-hidden="true" />
                  <span>{t.donationsPage.breadcrumbHome}</span>
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator className="text-white/50 [&>svg]:rtl:-scale-x-100" />
              <BreadcrumbItem>
                <BreadcrumbPage className="text-white">
                  {t.donationsPage.pageTitle}
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
      </section>

      <div className="bg-surface-soft py-12 md:py-16">
        <div className="mx-auto max-w-[1200px] px-6">
          <div className="flex flex-col gap-10 md:flex-row md:items-start md:gap-12">
            <CategorySidebar
              categories={categories}
              activeCategory={filters.category}
              searchValue={filters.search ?? ""}
              allLabel={t.donationsPage.allCategories}
              categoriesLabel={t.donationsPage.categories}
              searchPlaceholder={t.donationsPage.searchPlaceholder}
              searchButtonLabel={t.donationsPage.searchButton}
              clearSearchLabel={t.donationsPage.clearSearch}
              dir={dir}
              onSearchChange={setSearchInput}
            />

            <div className="min-w-0 flex-1">
              {loading ? (
                <CampaignSkeletonGrid />
              ) : campaigns.data.length > 0 ? (
                <>
                  <p className="mb-6 text-sm text-body-text/60">
                    {campaigns.total} {countLabel}
                  </p>
                  <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {campaigns.data.map((campaign) => (
                      <CampaignCard
                        key={campaign.id}
                        campaign={campaign}
                        donateLabel={t.donationsPage.donateNow}
                        goalReachedLabel={t.donationsPage.goalReached}
                      />
                    ))}
                  </div>
                  <CampaignPagination
                    currentPage={campaigns.current_page}
                    lastPage={campaigns.last_page}
                    search={{
                      search: filters.search,
                      category: filters.category,
                    }}
                    dir={dir}
                  />
                </>
              ) : (
                <div className="flex flex-col items-center justify-center py-24 text-center">
                  <p className="text-lg font-medium text-body-text/60">
                    {searchInput
                      ? t.donationsPage.noResultsSearch
                      : t.donationsPage.noResults}
                  </p>
                  {hasActiveFilters ? (
                    <button
                      type="button"
                      onClick={resetFilters}
                      className="mt-4 text-sm font-semibold text-action-red hover:underline"
                    >
                      {t.donationsPage.resetFilters}
                    </button>
                  ) : null}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

DonationsIndex.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
