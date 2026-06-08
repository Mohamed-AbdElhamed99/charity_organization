import { Link } from "@inertiajs/react";
import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { NewsCard, type NewsItem } from "./news-card";

export interface CampaignsSectionProps {
  t: SiteTranslations;
  campaigns: NewsItem[];
}

export function CampaignsSection({ t, campaigns }: CampaignsSectionProps) {
  return (
    <section id="campaigns" className="bg-surface py-20 md:py-28">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading
            eyebrow={t.campaigns.eyebrow}
            title={t.campaigns.title}
            intro={t.campaigns.intro}
          />
          <Link
            href="/campaigns"
            className="shrink-0 text-sm font-semibold text-action-red hover:underline"
          >
            {t.campaigns.seeMore}
          </Link>
        </div>

        {campaigns.length > 0 ? (
          <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {campaigns.map((item) => (
              <NewsCard
                key={item.id}
                item={item}
                readLabel={t.campaigns.readCampaign}
                hrefBase="/campaigns"
              />
            ))}
          </div>
        ) : (
          <p className="mt-12 text-center text-body-text/60">{t.campaigns.intro}</p>
        )}
      </div>
    </section>
  );
}

export default CampaignsSection;
