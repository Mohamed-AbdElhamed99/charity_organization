import { useEffect, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { SiteButton } from "@/components/site/site-button";
import { Spinner } from "@/components/ui/spinner";
import { formatUsd } from "@/lib/donation-fee";

type DonationSnapshot = {
  status: string;
  amount_cents: number;
  campaign_title: string | null;
  is_general: boolean;
  email?: string | null;
  is_recurring?: boolean;
  manage_subscription_url?: string | null;
};

type PageProps = {
  paymentIntentId: string;
  donation: DonationSnapshot | null;
};

const POLL_INTERVAL_MS = 2000;
const MAX_POLL_MS = 30000;

export default function ThankYouPage({ paymentIntentId, donation }: PageProps) {
  const { t } = useLocale();
  const labels = t.thankYouPage;

  const [status, setStatus] = useState(donation?.status ?? "pending");
  const [amountCents, setAmountCents] = useState(donation?.amount_cents ?? 0);
  const [email, setEmail] = useState<string | null>(donation?.email ?? null);
  const [isRecurring, setIsRecurring] = useState(donation?.is_recurring ?? false);
  const [manageSubscriptionUrl, setManageSubscriptionUrl] = useState<string | null>(
    donation?.manage_subscription_url ?? null,
  );
  const campaignTitle = donation?.campaign_title ?? null;
  const isGeneral = donation?.is_general ?? false;

  useEffect(() => {
    if (status === "succeeded" || status === "failed") {
      return;
    }

    const startedAt = Date.now();

    const interval = window.setInterval(async () => {
      if (Date.now() - startedAt > MAX_POLL_MS) {
        window.clearInterval(interval);
        return;
      }

      const response = await fetch(route("donations.status", paymentIntentId));
      if (!response.ok) {
        return;
      }

      const data = await response.json();
      setStatus(data.status);

      if (data.amount_cents) {
        setAmountCents(data.amount_cents);
      }

      if (data.email) {
        setEmail(data.email);
      }

      if (data.is_recurring) {
        setIsRecurring(true);
      }

      if (data.manage_subscription_url) {
        setManageSubscriptionUrl(data.manage_subscription_url);
      }

      if (data.status === "succeeded" || data.status === "failed") {
        window.clearInterval(interval);
      }
    }, POLL_INTERVAL_MS);

    return () => window.clearInterval(interval);
  }, [paymentIntentId, status]);

  // const isConfirming = status === "pending" || status === "requires_action" || status === "unknown";
  const isSuccess = status === "succeeded";
  const isFailed = status === "failed";
  const isConfirming = !isSuccess && !isFailed;

  return (
    <>
      <Head title={labels.title} />

      <section className="bg-surface-soft pb-20 pt-32">
        <div className="mx-auto max-w-xl px-6 text-center">
          {isConfirming && (
            <>
              <Spinner className="mx-auto mb-4 size-8 text-action-red" />
              <h1 className="font-display text-3xl font-bold text-ink">
                {labels.confirming}
              </h1>
              <p className="mt-4 text-body-text">{labels.confirmingBody}</p>
            </>
          )}

          {isSuccess && (
            <>
              <h1 className="font-display text-3xl font-bold text-ink">
                {labels.successTitle}
              </h1>
              <p className="mt-4 text-body-text">
                Your gift of {formatUsd(amountCents)} has been received.
                {isGeneral
                  ? ` ${labels.successGeneral}`
                  : campaignTitle
                    ? ` ${labels.successCampaign} ${campaignTitle}.`
                    : null}
              </p>
              {email ? (
                <p className="mt-2 text-sm text-body-text/70">
                  {labels.receiptNote} {email}.
                </p>
              ) : null}
              {isRecurring ? (
                <p className="mt-2 text-sm text-body-text/70">
                  {labels.recurringNote}
                </p>
              ) : null}
              {isRecurring && manageSubscriptionUrl ? (
                <p className="mt-4">
                  <a
                    href={manageSubscriptionUrl}
                    className="font-medium text-action-red hover:underline"
                  >
                    {labels.manageSubscription}
                  </a>
                </p>
              ) : null}
            </>
          )}

          {isFailed && (
            <>
              <h1 className="font-display text-3xl font-bold text-ink">
                {labels.failedTitle}
              </h1>
              <p className="mt-4 text-body-text">{labels.failedBody}</p>
              <SiteButton
                href={route("donate.general")}
                variant="primary"
                className="mt-6"
              >
                {labels.retry}
              </SiteButton>
            </>
          )}

          <p className="mt-8 text-sm text-body-text/70">
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

ThankYouPage.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
