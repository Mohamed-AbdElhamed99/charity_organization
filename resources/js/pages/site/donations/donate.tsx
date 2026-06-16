import { Head, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { DonationCheckout } from "@/components/site/donation-checkout";
import { SiteButton } from "@/components/site/site-button";
import type { FeeConfig } from "@/lib/donation-fee";
import type { CountryOption } from "@/components/site/donation-form/donor-fields";

type CampaignSummary = {
  id: number;
  slug: string;
  title: string;
  excerpt: string | null;
  thumbnail: string;
  goal_amount_cents: number | null;
  collected_amount_cents: number;
  open_donation_form: boolean;
  status: string;
};

type PageProps = {
  campaign: CampaignSummary | null;
  isGeneral: boolean;
  minAmountCents: number;
  stripePublishableKey: string;
  publishableKey: string;
  feeConfig: FeeConfig;
  countries: CountryOption[];
};

export default function DonatePage({
  campaign,
  isGeneral,
  minAmountCents,
  publishableKey,
  feeConfig,
  countries,
}: PageProps) {
  const { t } = useLocale();

  const title = isGeneral
    ? t.donatePage.generalTitle
    : campaign?.title ?? "Campaign";

  const hasGoal =
    campaign?.goal_amount_cents !== null &&
    campaign?.goal_amount_cents !== undefined &&
    campaign.goal_amount_cents > 0;
  const goalReached =
    hasGoal &&
    campaign!.collected_amount_cents >= campaign!.goal_amount_cents!;
  const unavailable = !isGeneral && (!campaign?.open_donation_form || goalReached);

  return (
    <>
      <Head title={title} />

      <section className="bg-ink pt-32 pb-12 text-white md:pt-40 md:pb-16">
        <div className="mx-auto max-w-[1200px] px-6">
          <span className="mb-4 block text-xs font-semibold uppercase tracking-[0.2em] text-gold">
            {t.donatePage.eyebrow}
          </span>
          <h1 className="font-display text-4xl font-bold leading-tight md:text-5xl">
            {title}
          </h1>
        </div>
      </section>

      <section className="bg-surface-soft pb-20 pt-10">
        <div className="mx-auto max-w-xl px-6">
          {!isGeneral && campaign ? (
            <div className="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
              <div className="aspect-[16/9] overflow-hidden bg-surface-soft">
                {campaign.thumbnail ? (
                  <img
                    src={campaign.thumbnail}
                    alt={campaign.title}
                    className="h-full w-full object-cover"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center">
                    <img
                      src="/images/new-egypt-logo.png"
                      alt=""
                      className="h-16 w-16 object-contain opacity-40"
                    />
                  </div>
                )}
              </div>
              <div className="p-5">
                <h2 className="font-display text-xl font-bold text-ink">
                  {campaign.title}
                </h2>
                {campaign.excerpt ? (
                  <p className="mt-2 text-sm text-body-text">{campaign.excerpt}</p>
                ) : null}
              </div>
            </div>
          ) : null}

          {unavailable ? (
            <div className="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
              <p className="text-body-text">
                {goalReached
                  ? t.donatePage.goalReachedNotice
                  : t.donatePage.campaignUnavailable}
              </p>
              <SiteButton
                href={route("donate.general")}
                variant="primary"
                className="mt-6"
              >
                {t.donatePage.generalDonateCta}
              </SiteButton>
            </div>
          ) : (
            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
              <DonationCheckout
                campaign={campaign}
                isGeneral={isGeneral}
                minAmountCents={minAmountCents}
                publishableKey={publishableKey}
                feeConfig={feeConfig}
                countries={countries}
                t={t}
                disabled={unavailable}
                goalReached={goalReached}
              />
            </div>
          )}

          <p className="mt-6 text-center text-sm text-body-text/70">
            <Link
              href={route("donations.index")}
              className="font-medium text-action-red hover:underline"
            >
              {t.campaignsPage.backToCampaigns}
            </Link>
          </p>
        </div>
      </section>
    </>
  );
}

DonatePage.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
