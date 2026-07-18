import { Head, router } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { formatUsd } from "@/lib/donation-fee";

type Allocation = {
  id: number;
  is_general: boolean;
  amount_cents: number;
  campaign?: { title: string } | null;
};

type Subscription = {
  id: number;
  stripe_subscription_id: string;
  status: string;
  frequency: string;
  amount_cents: number;
  allocations?: Allocation[];
};

type DonationRecord = {
  id: number;
  amount: number;
  status: string;
  is_general: boolean;
  is_recurring: boolean;
  created_at: string;
  campaign?: { title: string } | null;
};

export default function AccountDonations({
  donations,
  subscriptions,
}: {
  donations: { data: DonationRecord[] };
  subscriptions: Subscription[];
}) {
  const { t, dir } = useLocale();
  const i18n = t.accountPage.donations;

  const cancelSubscription = (subscription: Subscription) => {
    if (!window.confirm(i18n.cancelConfirm)) {
      return;
    }
    router.post(route("account.subscriptions.cancel", subscription.id), {}, { preserveScroll: true });
  };

  return (
    <>
      <Head title={i18n.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-4xl px-6">
          <h1 className="font-display text-3xl font-extrabold text-ink">{i18n.title}</h1>

          <div className="mt-10">
            <h2 className="text-xl font-semibold text-ink">{i18n.subscriptionsTitle}</h2>

            {subscriptions.length === 0 ? (
              <p className="mt-3 text-sm text-body-text">{i18n.noSubscriptions}</p>
            ) : (
              <div className="mt-4 space-y-3">
                {subscriptions.map((subscription) => (
                  <div
                    key={subscription.id}
                    className="flex items-center justify-between rounded-xl border border-surface-soft bg-white p-4"
                    dir={dir}
                  >
                    <div>
                      <p className="text-sm font-semibold text-ink">
                        {formatUsd(subscription.amount_cents)} / {subscription.frequency}
                      </p>
                      <p className="text-xs text-body-text">
                        {subscription.allocations?.map((allocation) => allocation.campaign?.title ?? i18n.general).join(", ")}
                      </p>
                      <p className="text-xs font-medium text-body-text">
                        {i18n.status}: {subscription.status}
                      </p>
                    </div>
                    {subscription.status === "active" && (
                      <button
                        type="button"
                        onClick={() => cancelSubscription(subscription)}
                        className="text-xs font-semibold text-red-600"
                      >
                        {i18n.cancelSubscription}
                      </button>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="mt-12">
            <h2 className="text-xl font-semibold text-ink">{i18n.historyTitle}</h2>

            {donations.data.length === 0 ? (
              <p className="mt-3 text-sm text-body-text">{i18n.noDonations}</p>
            ) : (
              <div className="mt-4 overflow-x-auto">
                <table className="w-full text-left text-sm" dir={dir}>
                  <thead>
                    <tr className="border-b border-surface-soft text-body-text">
                      <th className="py-2">{i18n.date}</th>
                      <th className="py-2">{i18n.campaign}</th>
                      <th className="py-2">{i18n.amount}</th>
                      <th className="py-2">{i18n.status}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {donations.data.map((donation) => (
                      <tr key={donation.id} className="border-b border-surface-soft">
                        <td className="py-2">{new Date(donation.created_at).toLocaleDateString()}</td>
                        <td className="py-2">{donation.is_general ? i18n.general : donation.campaign?.title}</td>
                        <td className="py-2">{formatUsd(donation.amount)}</td>
                        <td className="py-2 capitalize">{donation.status}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
}

AccountDonations.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
