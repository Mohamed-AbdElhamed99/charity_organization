import { ArrowRight } from "lucide-react";
import { Link } from "@inertiajs/react";

export interface NewsItem {
  id: string | number;
  slug: string;
  title: string;
  excerpt: string | null;
  thumbnail: string;
  category_name: string | null;
  published_at: string | null;
}

export interface NewsCardProps {
  item: NewsItem;
  readLabel: string;
  hrefBase?: string;
}

export function NewsCard({ item, readLabel, hrefBase = "/news" }: NewsCardProps) {
  return (
    <Link
      href={`${hrefBase}/${item.slug}`}
      className="group flex flex-col overflow-hidden rounded-2xl bg-surface shadow-sm ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
    >
      <div className="aspect-[16/10] overflow-hidden bg-surface-soft">
        {item.thumbnail ? (
          <img
            src={item.thumbnail}
            alt={item.title}
            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            loading="lazy"
          />
        ) : (
          <div className="h-full w-full" />
        )}
      </div>
      <div className="flex flex-1 flex-col gap-3 p-6">
        {item.category_name ? (
          <span className="text-xs font-semibold uppercase tracking-widest text-gold">
            {item.category_name}
          </span>
        ) : null}
        <h3 className="font-display text-lg font-bold text-ink leading-snug">
          {item.title}
        </h3>
        {item.excerpt ? (
          <p className="text-sm text-body-text leading-relaxed line-clamp-3">
            {item.excerpt}
          </p>
        ) : null}
        {item.published_at ? (
          <time className="text-xs text-body-text/60">{item.published_at}</time>
        ) : null}
        <span className="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-action-red">
          {readLabel}
          <ArrowRight className="h-4 w-4 rtl:-scale-x-100 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
        </span>
      </div>
    </Link>
  );
}

export default NewsCard;
