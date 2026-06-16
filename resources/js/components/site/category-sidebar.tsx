import { Search, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { router } from "@inertiajs/react";
import { route } from "ziggy-js";

export type CategoryItem = {
  id: number;
  name: string;
  slug: string;
  count: number;
};

type CategorySidebarProps = {
  categories: CategoryItem[];
  activeCategory: string | undefined;
  searchValue: string;
  allLabel: string;
  categoriesLabel: string;
  searchPlaceholder: string;
  searchButtonLabel: string;
  clearSearchLabel: string;
  dir: "ltr" | "rtl";
  onSearchChange: (value: string) => void;
};

export function CategorySidebar({
  categories,
  activeCategory,
  searchValue,
  allLabel,
  categoriesLabel,
  searchPlaceholder,
  searchButtonLabel,
  clearSearchLabel,
  dir,
  onSearchChange,
}: CategorySidebarProps) {
  const searchRef = useRef<HTMLInputElement>(null);
  const [localSearch, setLocalSearch] = useState(searchValue);

  useEffect(() => {
    setLocalSearch(searchValue);
  }, [searchValue]);

  useEffect(() => {
    const timeout = window.setTimeout(() => {
      if (localSearch === searchValue) {
        return;
      }

      router.get(
        route("donations.index"),
        {
          search: localSearch || undefined,
          category: activeCategory || undefined,
        },
        {
          preserveState: true,
          preserveScroll: true,
          replace: true,
          only: ["campaigns", "filters"],
        },
      );
      onSearchChange(localSearch);
    }, 350);

    return () => window.clearTimeout(timeout);
  }, [localSearch, searchValue, activeCategory, onSearchChange]);

  const selectCategory = (categoryId?: string) => {
    router.get(
      route("donations.index"),
      {
        search: localSearch || undefined,
        category: categoryId || undefined,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["campaigns", "filters"],
      },
    );
  };

  const handleSearchSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    router.get(
      route("donations.index"),
      {
        search: localSearch || undefined,
        category: activeCategory || undefined,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["campaigns", "filters"],
      },
    );
    onSearchChange(localSearch);
  };

  return (
    <aside className="w-full shrink-0 md:w-64">
      <div className="rounded-2xl bg-surface-soft p-5 ring-1 ring-black/5">
        <form onSubmit={handleSearchSubmit} className="mb-6">
          <div className="relative">
            <Search
              className="pointer-events-none absolute top-1/2 -translate-y-1/2 h-4 w-4 text-body-text/40 ltr:left-3 rtl:right-3"
              aria-hidden="true"
            />
            <input
              ref={searchRef}
              type="search"
              value={localSearch}
              onChange={(event) => setLocalSearch(event.target.value)}
              placeholder={searchPlaceholder}
              dir={dir}
              className="h-11 w-full rounded-lg border border-black/10 bg-white py-2 text-sm text-ink placeholder:text-body-text/40 outline-none transition focus:border-action-red focus:ring-2 focus:ring-action-red/20 ltr:pl-9 ltr:pr-4 rtl:pr-9 rtl:pl-4"
            />
            {localSearch ? (
              <button
                type="button"
                onClick={() => setLocalSearch("")}
                aria-label={clearSearchLabel}
                className="absolute top-1/2 -translate-y-1/2 text-body-text/40 hover:text-ink ltr:right-3 rtl:left-3"
              >
                <X className="h-4 w-4" />
              </button>
            ) : null}
          </div>
          <button
            type="submit"
            className="mt-3 w-full rounded-lg bg-action-red px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-action-red/90 focus:outline-none focus:ring-2 focus:ring-action-red/40"
          >
            {searchButtonLabel}
          </button>
        </form>

        <p className="mb-3 text-xs font-semibold uppercase tracking-widest text-gold">
          {categoriesLabel}
        </p>
        <ul className="space-y-1">
          <li>
            <button
              type="button"
              onClick={() => selectCategory(undefined)}
              aria-pressed={!activeCategory}
              className={`w-full rounded-full px-4 py-2.5 text-start text-sm font-medium transition-colors ${
                !activeCategory
                  ? "bg-action-red text-white"
                  : "text-ink hover:bg-white"
              }`}
            >
              {allLabel}
            </button>
          </li>
          {categories.map((category) => (
            <li key={category.id}>
              <button
                type="button"
                onClick={() => selectCategory(String(category.id))}
                aria-pressed={activeCategory === String(category.id)}
                className={`w-full rounded-full px-4 py-2.5 text-start text-sm font-medium transition-colors ${
                  activeCategory === String(category.id)
                    ? "bg-action-red text-white"
                    : "text-ink hover:bg-white"
                }`}
              >
                <span>{category.name}</span>
                {category.count > 0 ? (
                  <span className="ms-2 text-xs opacity-80">({category.count})</span>
                ) : null}
              </button>
            </li>
          ))}
        </ul>
      </div>
    </aside>
  );
}

export default CategorySidebar;
