import { usePage } from "@inertiajs/react";
import { SiteLayout } from "@/layouts/site-layout";
import { SiteHero } from "@/components/site/site-hero";
import { MissionSection } from "@/components/site/mission-section";
import { MessageSection } from "@/components/site/message-section";
import { DonationCallout } from "@/components/site/donation-callout";
import { NewsSection } from "@/components/site/news-section";
import { ActivitiesSection } from "@/components/site/activities-section";
import { VolunteersSection } from "@/components/site/volunteers-section";
import { useLocale } from "@/context/locale-context";
import type { NewsItem } from "@/components/site/news-card";

type PageProps = {
  latestNews: NewsItem[];
};

export default function Home() {
  const { t } = useLocale();
  const { latestNews } = usePage<PageProps>().props;

  return (
    <>
      <SiteHero t={t} />
      <MissionSection t={t} />
      <MessageSection t={t} />
      <DonationCallout t={t} />
      <NewsSection t={t} news={latestNews ?? []} />
      <ActivitiesSection t={t} />
      <VolunteersSection t={t} />
    </>
  );
}

Home.layout = (page: React.ReactNode) => <SiteLayout>{page}</SiteLayout>;
