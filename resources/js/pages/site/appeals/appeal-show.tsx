import { useMemo, useState } from "react";
import { Head } from "@inertiajs/react";
import { DonationCheckout } from "@/components/site/donation-checkout";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { dollarsToCents } from "@/lib/donation-fee";
import type { FeeConfig } from "@/lib/donation-fee";
import type { CountryOption } from "@/components/site/donation-form/donor-fields";
import { cn } from "@/lib/utils";

type GiftOption = {
  name: string;
  amount_cents: number;
};

type Appeal = {
  id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  gift_options: GiftOption[];
  allow_custom_amount: boolean;
  cta_label: string;
  image: string;
  campaign: {
    id: number;
    slug: string;
    title: string;
  };
};

type Organization = {
  legalName: string;
  taxStatus: string;
  ein: string;
  website: string;
};

type PageProps = {
  appeal: Appeal;
  minAmountCents: number;
  publishableKey: string;
  feeConfig: FeeConfig;
  countries: CountryOption[];
  organization: Organization;
};

function formatAppealMoney(cents: number): string {
  const wholeDollars = cents % 100 === 0;

  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: wholeDollars ? 0 : 2,
    maximumFractionDigits: wholeDollars ? 0 : 2,
  }).format(cents / 100);
}

export default function AppealShow({
  appeal,
  minAmountCents,
  publishableKey,
  feeConfig,
  countries,
  organization,
}: PageProps) {
  const { t } = useLocale();
  const labels = t.donatePage;
  const [selectedOption, setSelectedOption] = useState(0);
  const [useCustom, setUseCustom] = useState(false);
  const [customAmount, setCustomAmount] = useState("");

  const customCents = dollarsToCents(customAmount) ?? 0;
  const chosenGift = appeal.gift_options[selectedOption] ?? appeal.gift_options[0];
  const amountCents = useCustom ? customCents : (chosenGift?.amount_cents ?? 0);

  const websiteHref = organization.website.startsWith("http")
    ? organization.website
    : `https://${organization.website}`;

  const lockedGift = useMemo(
    () => ({
      appealId: appeal.id,
      amountCents,
      giftOption: useCustom ? null : selectedOption,
      submitLabel: appeal.cta_label,
    }),
    [appeal.id, appeal.cta_label, amountCents, selectedOption, useCustom],
  );

  return (
    <>
      <Head title={appeal.title} />

      <section className="bg-white px-4 pb-16 pt-28 md:pt-32">
        <div className="mx-auto w-full max-w-md">
          <div className="mb-6 flex justify-center">
            <img
              src="/images/new-egypt-logo.png"
              alt="New Egypt Group"
              className="h-20 w-20 rounded-full object-contain ring-1 ring-black/10"
            />
          </div>

          <article className="overflow-hidden">
            <div className="overflow-hidden rounded-2xl bg-surface-soft">
              {appeal.image ? (
                <img
                  src={appeal.image}
                  alt={appeal.title}
                  className="aspect-[4/3] w-full object-cover"
                />
              ) : (
                <div className="flex aspect-[4/3] items-center justify-center">
                  <img
                    src="/images/new-egypt-logo.png"
                    alt=""
                    className="h-16 w-16 object-contain opacity-40"
                  />
                </div>
              )}
            </div>

            <h1 className="mt-6 text-center font-display text-3xl font-bold leading-tight text-ink">
              {appeal.title}
            </h1>
            {appeal.subtitle ? (
              <p className="mt-2 text-center text-base text-body-text">{appeal.subtitle}</p>
            ) : null}

            <fieldset className="mt-6">
              <legend className="mb-3 w-full text-center text-base font-bold text-ink">
                {labels.chooseDonation}
              </legend>
              <div className="grid grid-cols-2 gap-3">
                {appeal.gift_options.map((option, index) => {
                  const selected = !useCustom && selectedOption === index;

                  return (
                    <label
                      key={`${option.name}-${option.amount_cents}`}
                      className={cn(
                        "flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 px-3 py-4 text-center transition focus-within:ring-2 focus-within:ring-action-red/40",
                        selected
                          ? "border-action-red bg-action-red/5"
                          : "border-black/10 hover:border-black/20",
                      )}
                    >
                      <input
                        type="radio"
                        name="appeal-gift"
                        className="sr-only"
                        checked={selected}
                        onChange={() => {
                          setUseCustom(false);
                          setSelectedOption(index);
                          setCustomAmount("");
                        }}
                      />
                      <span className="font-display text-4xl font-bold text-action-red">
                        {formatAppealMoney(option.amount_cents)}
                      </span>
                      <span className="mt-1 text-lg font-bold text-ink">{option.name}</span>
                    </label>
                  );
                })}
              </div>
            </fieldset>

            {appeal.allow_custom_amount ? (
              <div className="mt-4 text-center">
                <button
                  type="button"
                  className="text-sm font-medium text-ink underline underline-offset-4"
                  onClick={() => setUseCustom(true)}
                >
                  {labels.otherAmount}
                </button>
                {useCustom ? (
                  <label className="mt-3 block text-start text-sm font-medium text-ink">
                    {labels.customAmount}
                    <input
                      type="number"
                      min={minAmountCents / 100}
                      step="0.01"
                      value={customAmount}
                      onChange={(event) => setCustomAmount(event.target.value)}
                      placeholder={labels.customAmountPlaceholder}
                      className="mt-1 w-full rounded-lg border border-black/10 px-4 py-2.5 text-sm outline-none focus:border-action-red focus:ring-2 focus:ring-action-red/20"
                    />
                  </label>
                ) : null}
              </div>
            ) : null}

            <div className="mt-5 rounded-xl border border-black/10 p-4">
              <p className="text-sm font-semibold text-action-red">{labels.yourDonation}</p>
              <p className="mt-1 text-sm text-body-text">
                {useCustom ? labels.customGiftLine : chosenGift?.name}
              </p>
              <p className="mt-2 text-lg font-bold text-ink">
                {labels.appealTotal}:{" "}
                <span className="text-action-red">{formatAppealMoney(amountCents)} USD</span>
              </p>
            </div>

            <div className="mt-5">
              <DonationCheckout
                campaign={{
                  id: appeal.campaign.id,
                  slug: appeal.campaign.slug,
                  title: appeal.campaign.title,
                }}
                isGeneral={false}
                minAmountCents={minAmountCents}
                publishableKey={publishableKey}
                feeConfig={feeConfig}
                countries={countries}
                t={t}
                lockedGift={lockedGift}
              />
            </div>
          </article>

          <footer className="mt-8 text-center text-xs leading-5 text-body-text/80">
            <p>
              {organization.legalName} {labels.legalIsA} {organization.taxStatus}{" "}
              {labels.legalNonprofit}
            </p>
            <p className="mt-2">
              {labels.einLabel}: {organization.ein}
            </p>
            <a href={websiteHref} className="mt-1 inline-block font-medium text-ink hover:underline">
              {organization.website.replace(/^https?:\/\//, "")}
            </a>
          </footer>
        </div>
      </section>
    </>
  );
}

AppealShow.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
