import { ArrowRight } from "lucide-react";

export interface NewsItem {
  id: string | number;
  title: string;
  excerpt: string;
  image: string;
  href: string;
  date?: string;
  category?: string;
}

export interface NewsCardProps {
  item: NewsItem;
  readLabel: string;
}

export function NewsCard({ item, readLabel }: NewsCardProps) {
  return (
    <a
      href={item.href}
      className="group flex flex-col overflow-hidden rounded-2xl bg-surface shadow-sm ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
    >
      <div className="aspect-[16/10] overflow-hidden">
        <img
          src={item.image}
          alt=""
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
          loading="lazy"
        />
      </div>
      <div className="flex flex-1 flex-col gap-3 p-6">
        {item.category ? (
          <span className="text-xs font-semibold uppercase tracking-widest text-gold">
            {item.category}
          </span>
        ) : null}
        <h3 className="font-display text-lg font-bold text-ink leading-snug">
          {item.title}
        </h3>
        <p className="text-sm text-body-text leading-relaxed line-clamp-3">
          {item.excerpt}
        </p>
        <span className="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-action-red">
          {readLabel}
          <ArrowRight className="h-4 w-4 rtl:-scale-x-100 transition-transform group-hover:translate-x-1" />
        </span>
      </div>
    </a>
  );
}

export default NewsCard;