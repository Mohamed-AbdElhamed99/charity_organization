import { Link } from "@inertiajs/react";
import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { NewsCard, type NewsItem } from "./news-card";

export interface NewsSectionProps {
  t: SiteTranslations;
  news: NewsItem[];
}

export function NewsSection({ t, news }: NewsSectionProps) {
  return (
    <section id="news" className="bg-surface-soft py-20 md:py-28">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading
            eyebrow={t.news.eyebrow}
            title={t.news.title}
            intro={t.news.intro}
          />
          <Link
            href="/news"
            className="shrink-0 text-sm font-semibold text-action-red hover:underline"
          >
            {t.news.seeMore}
          </Link>
        </div>

        {news.length > 0 ? (
          <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {news.map((item) => (
              <NewsCard key={item.id} item={item} readLabel={t.news.readArticle} />
            ))}
          </div>
        ) : (
          <p className="mt-12 text-center text-body-text/60">{t.news.intro}</p>
        )}
      </div>
    </section>
  );
}

export default NewsSection;
