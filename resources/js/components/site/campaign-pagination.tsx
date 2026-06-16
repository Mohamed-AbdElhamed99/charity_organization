import { router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { route } from "ziggy-js";
import { cn, getPageNumbers } from "@/lib/utils";

type CampaignPaginationProps = {
  currentPage: number;
  lastPage: number;
  search: Record<string, unknown>;
  dir?: "ltr" | "rtl";
};

export function CampaignPagination({
  currentPage,
  lastPage,
  search,
  dir = "ltr",
}: CampaignPaginationProps) {
  if (lastPage <= 1) {
    return null;
  }

  const navigate = (page: number) => {
    router.get(
      route("donations.index"),
      { ...search, page },
      { preserveScroll: true, only: ["campaigns", "filters"] },
    );
  };

  const pageNumbers = getPageNumbers(currentPage, lastPage);

  const baseBtn =
    "inline-flex size-10 items-center justify-center rounded-full text-sm font-medium transition-colors";
  const activeBtn = "bg-action-red text-white shadow-sm";
  const inactiveBtn =
    "bg-surface text-ink ring-1 ring-black/10 hover:bg-surface-soft";
  const disabledBtn = "opacity-40 pointer-events-none";

  return (
    <nav
      className="mt-10 flex items-center justify-center gap-2"
      aria-label="Pagination"
    >
      <button
        type="button"
        onClick={() => navigate(currentPage - 1)}
        disabled={currentPage <= 1}
        className={cn(baseBtn, inactiveBtn, currentPage <= 1 && disabledBtn)}
        aria-label="Previous page"
      >
        {dir === "rtl" ? (
          <ChevronRight className="size-4" />
        ) : (
          <ChevronLeft className="size-4" />
        )}
      </button>

      {pageNumbers.map((page, index) =>
        page === "ellipsis" ? (
          <span key={`ellipsis-${index}`} className="px-2 text-body-text/50">
            …
          </span>
        ) : (
          <button
            key={page}
            type="button"
            onClick={() => navigate(page as number)}
            aria-current={currentPage === page ? "page" : undefined}
            className={cn(
              baseBtn,
              currentPage === page ? activeBtn : inactiveBtn,
            )}
          >
            {page}
          </button>
        ),
      )}

      <button
        type="button"
        onClick={() => navigate(currentPage + 1)}
        disabled={currentPage >= lastPage}
        className={cn(
          baseBtn,
          inactiveBtn,
          currentPage >= lastPage && disabledBtn,
        )}
        aria-label="Next page"
      >
        {dir === "rtl" ? (
          <ChevronLeft className="size-4" />
        ) : (
          <ChevronRight className="size-4" />
        )}
      </button>
    </nav>
  );
}

export default CampaignPagination;
