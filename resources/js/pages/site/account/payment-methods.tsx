import { useMemo, useState } from "react";
import { Head, router } from "@inertiajs/react";
import { loadStripe } from "@stripe/stripe-js";
import {
  Elements,
  PaymentElement,
  useElements,
  useStripe,
} from "@stripe/react-stripe-js";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { SiteButton } from "@/components/site/site-button";

type SavedPaymentMethod = {
  id: number;
  brand: string;
  last4: string;
  exp_month: number;
  exp_year: number;
  is_default: boolean;
};

function AddCardForm({
  onSaved,
  onCancel,
}: {
  onSaved: () => void;
  onCancel: () => void;
}) {
  const { t } = useLocale();
  const i18n = t.accountPage.paymentMethods;
  const stripe = useStripe();
  const elements = useElements();
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!stripe || !elements) {
      return;
    }

    setProcessing(true);
    setError("");

    const { error: submitError, setupIntent } = await stripe.confirmSetup({
      elements,
      redirect: "if_required",
    });

    if (submitError) {
      setError(submitError.message ?? "");
      setProcessing(false);
      return;
    }

    const paymentMethodId =
      typeof setupIntent?.payment_method === "string"
        ? setupIntent.payment_method
        : setupIntent?.payment_method?.id;

    if (!paymentMethodId) {
      setError("Could not confirm card.");
      setProcessing(false);
      return;
    }

    router.post(
      route("account.payment-methods.store"),
      { payment_method_id: paymentMethodId },
      {
        onFinish: () => {
          setProcessing(false);
          onSaved();
        },
      },
    );
  };

  return (
    <form onSubmit={handleSubmit} className="mt-6 space-y-4 rounded-xl border border-surface-soft bg-white p-5">
      <PaymentElement />
      {error && <p className="text-sm text-red-600">{error}</p>}
      <div className="flex gap-3">
        <SiteButton type="submit" disabled={!stripe || processing}>
          {processing ? "..." : i18n.saveCard}
        </SiteButton>
        <button type="button" onClick={onCancel} className="text-sm font-medium text-body-text">
          {i18n.cancel}
        </button>
      </div>
    </form>
  );
}

export default function AccountPaymentMethods({
  paymentMethods,
  stripePublishableKey,
}: {
  paymentMethods: { data: SavedPaymentMethod[] } | SavedPaymentMethod[];
  stripePublishableKey: string;
}) {
  const { t, dir } = useLocale();
  const i18n = t.accountPage.paymentMethods;
  const [adding, setAdding] = useState(false);
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const stripePromise = useMemo(() => loadStripe(stripePublishableKey), [stripePublishableKey]);

  const methods = Array.isArray(paymentMethods) ? paymentMethods : paymentMethods.data;

  const startAddCard = async () => {
    const token =
      document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

    const response = await fetch(route("account.payment-methods.setup-intent"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": token,
      },
    });

    const payload = await response.json().catch(() => null);

    if (response.ok && payload?.client_secret) {
      setClientSecret(payload.client_secret);
      setAdding(true);
    }
  };

  const cancelAdd = () => {
    setAdding(false);
    setClientSecret(null);
  };

  const finishAdd = () => {
    setAdding(false);
    setClientSecret(null);
    router.reload({ only: ["paymentMethods"] });
  };

  const makeDefault = (id: number) => {
    router.post(route("account.payment-methods.default", id), {}, { preserveScroll: true });
  };

  const remove = (id: number) => {
    router.delete(route("account.payment-methods.destroy", id), { preserveScroll: true });
  };

  return (
    <>
      <Head title={i18n.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-2xl px-6">
          <h1 className="font-display text-3xl font-extrabold text-ink">{i18n.title}</h1>
          <p className="mt-3 text-body-text">{i18n.intro}</p>

          <div className="mt-8 space-y-4">
            {methods.length === 0 && (
              <p className="text-sm text-body-text">{i18n.noSaved}</p>
            )}

            {methods.map((method) => (
              <div
                key={method.id}
                className="flex items-center justify-between rounded-xl border border-surface-soft bg-white p-4"
                dir={dir}
              >
                <div>
                  <p className="text-sm font-semibold text-ink capitalize">
                    {method.brand} — {i18n.cardEndingIn} {method.last4}
                  </p>
                  <p className="text-xs text-body-text">
                    {i18n.expires} {method.exp_month}/{method.exp_year}
                  </p>
                </div>
                <div className="flex items-center gap-3">
                  {method.is_default ? (
                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                      {i18n.default}
                    </span>
                  ) : (
                    <button
                      type="button"
                      onClick={() => makeDefault(method.id)}
                      className="text-xs font-semibold text-action-red"
                    >
                      {i18n.makeDefault}
                    </button>
                  )}
                  <button
                    type="button"
                    onClick={() => remove(method.id)}
                    className="text-xs font-semibold text-body-text hover:text-red-600"
                  >
                    {i18n.remove}
                  </button>
                </div>
              </div>
            ))}
          </div>

          {!adding && (
            <SiteButton type="button" className="mt-6" onClick={startAddCard}>
              {i18n.addNew}
            </SiteButton>
          )}

          {adding && clientSecret && (
            <Elements stripe={stripePromise} options={{ clientSecret }}>
              <AddCardForm onSaved={finishAdd} onCancel={cancelAdd} />
            </Elements>
          )}
        </div>
      </section>
    </>
  );
}

AccountPaymentMethods.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
