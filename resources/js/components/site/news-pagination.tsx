import { router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn, getPageNumbers } from "@/lib/utils";

type NewsPaginationProps = {
  currentPage: number;
  lastPage: number;
  search: Record<string, unknown>;
  indexUrl?: string;
  dir?: "ltr" | "rtl";
};

export function NewsPagination({
  currentPage,
  lastPage,
  search,
  indexUrl = "/news",
  dir = "ltr",
}: NewsPaginationProps) {
  if (lastPage <= 1) {
    return null;
  }

  const navigate = (page: number) => {
    router.get(
      indexUrl,
      { ...search, page },
      { preserveScroll: true }
    );
  };

  const pageNumbers = getPageNumbers(currentPage, lastPage);

  const baseBtn =
    "inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors";
  const activeBtn = "bg-action-red text-white shadow-sm";
  const inactiveBtn = "bg-surface text-ink ring-1 ring-black/10 hover:bg-surface-soft";
  const disabledBtn = "opacity-40 pointer-events-none";

  return (
    <nav
      aria-label="Pagination"
      className="mt-12 flex items-center justify-center gap-2 flex-wrap"
      dir={dir}
    >
      <button
        onClick={() => navigate(currentPage - 1)}
        disabled={currentPage <= 1}
        aria-label="Previous page"
        className={cn(baseBtn, inactiveBtn, currentPage <= 1 && disabledBtn)}
      >
        {dir === "rtl" ? (
          <ChevronRight className="h-4 w-4" />
        ) : (
          <ChevronLeft className="h-4 w-4" />
        )}
      </button>

      {pageNumbers.map((pageNumber, index) =>
        pageNumber === "..." ? (
          <span
            key={`ellipsis-${index}`}
            className="inline-flex h-10 min-w-10 items-center justify-center px-1 text-sm text-body-text/50"
          >
            …
          </span>
        ) : (
          <button
            key={pageNumber}
            onClick={() => navigate(Number(pageNumber))}
            aria-label={`Page ${pageNumber}`}
            aria-current={currentPage === pageNumber ? "page" : undefined}
            className={cn(
              baseBtn,
              currentPage === pageNumber ? activeBtn : inactiveBtn
            )}
          >
            {pageNumber}
          </button>
        )
      )}

      <button
        onClick={() => navigate(currentPage + 1)}
        disabled={currentPage >= lastPage}
        aria-label="Next page"
        className={cn(baseBtn, inactiveBtn, currentPage >= lastPage && disabledBtn)}
      >
        {dir === "rtl" ? (
          <ChevronLeft className="h-4 w-4" />
        ) : (
          <ChevronRight className="h-4 w-4" />
        )}
      </button>
    </nav>
  );
}
