import { useState } from "react";
import {
  translations,
  type Locale,
} from "@/lib/translations";
import { SiteLayout } from "@/layouts/site-layout";
import { SiteHero } from "@/components/site/site-hero";
import { MissionSection } from "@/components/site/mission-section";
import { MessageSection } from "@/components/site/message-section";
import { DonationCallout } from "@/components/site/donation-callout";
import { NewsSection } from "@/components/site/news-section";
import { ActivitiesSection } from "@/components/site/activities-section";
import { VolunteersSection } from "@/components/site/volunteers-section";
import { useLocale } from "@/context/locale-context";

export interface HomeProps {
  /** When wired to Inertia, pass props.locale from Laravel. */
  initialLocale?: Locale;
}

/**
 * Home page composition. In production Laravel will hydrate this via Inertia
 * and pass the active locale + translations as page props. The local state
 * below is only here so the preview can toggle AR/EN without a backend.
 */
export function Home({ initialLocale = "en" }: HomeProps) {
  const { t } = useLocale();

  return (
    // <SiteLayout locale={locale} onLocaleChange={setLocale}>
    <>
      <SiteHero t={t} />
      <MissionSection t={t} />
      <MessageSection t={t} />
      <DonationCallout t={t} />
      <NewsSection t={t} />
      <ActivitiesSection t={t} />
      <VolunteersSection t={t} />
    </>

  );
}

export default Home;
Home.layout = (page: React.ReactNode) => <SiteLayout>{page}</SiteLayout>;
