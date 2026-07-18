import { useEffect, useMemo, useState } from "react";
import { loadStripe, type Stripe } from "@stripe/stripe-js";
import {
  Elements,
  ExpressCheckoutElement,
  PaymentElement,
  useElements,
  useStripe,
} from "@stripe/react-stripe-js";
import { route } from "ziggy-js";
import { AmountChips } from "@/components/site/donation-form/amount-chips";
import {
  AllocationEditor,
  createAllocationRow,
  type AllocationRow,
  type CampaignOption,
} from "@/components/site/donation-form/allocation-editor";
import { FeeToggle } from "@/components/site/donation-form/fee-toggle";
import {
  DonorFields,
  type CountryOption,
  type DonorFieldErrors,
} from "@/components/site/donation-form/donor-fields";
import { SiteButton } from "@/components/site/site-button";
import { Skeleton } from "@/components/ui/skeleton";
import { Spinner } from "@/components/ui/spinner";
import {
  calculateDonationBreakdown,
  dollarsToCents,
  formatUsd,
  type FeeConfig,
} from "@/lib/donation-fee";
import type { SiteTranslations } from "@/lib/translations";

type CampaignSummary = {
  id: number;
  slug: string;
  title: string;
  thumbnail?: string;
  goal_amount_cents?: number | null;
  collected_amount_cents?: number;
  open_donation_form?: boolean;
  status?: string;
};

type DonationCheckoutProps = {
  campaign: CampaignSummary | null;
  isGeneral: boolean;
  minAmountCents: number;
  publishableKey: string;
  feeConfig: FeeConfig;
  countries: CountryOption[];
  t: SiteTranslations;
  disabled?: boolean;
  goalReached?: boolean;
};

type BreakdownSnapshot = {
  amountCents: number;
  chargeCents: number;
  estimatedFeeCents: number;
};

type RecurrenceFrequency = "one_time" | "weekly" | "monthly" | "quarterly" | "yearly";

const frequencyButtonActiveClass = "rounded-md px-4 py-2 text-sm font-medium transition bg-white text-ink shadow-sm";
const frequencyButtonInactiveClass = "rounded-md px-4 py-2 text-sm font-medium transition text-body-text hover:text-ink";

function PaymentStep({
  paymentIntentId,
  chargeCents,
  isRecurring,
  labels,
  onError,
  onBack,
}: {
  paymentIntentId: string;
  chargeCents: number;
  isRecurring: boolean;
  labels: SiteTranslations["donatePage"];
  onError: (message: string) => void;
  onBack: () => void;
}) {
  const stripe = useStripe();
  const elements = useElements();
  const [processing, setProcessing] = useState(false);
  const [elementReady, setElementReady] = useState(false);
  const [expressCheckoutReady, setExpressCheckoutReady] = useState(false);

  const confirmPayment = async () => {
    if (!stripe || !elements) {
      return;
    }

    setProcessing(true);
    onError("");

    const result = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: route("donations.thank-you", paymentIntentId),
      },
    });

    if (result.error) {
      onError(result.error.message ?? labels.paymentError);
    }

    setProcessing(false);
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    await confirmPayment();
  };

  const handleExpressCheckoutConfirm = async () => {
    await confirmPayment();
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className={expressCheckoutReady ? "" : "h-0 overflow-hidden"}>
        <ExpressCheckoutElement
          onReady={({ availablePaymentMethods }) =>
            setExpressCheckoutReady(Boolean(availablePaymentMethods))
          }
          onConfirm={handleExpressCheckoutConfirm}
        />
      </div>
      {expressCheckoutReady ? (
        <div className="flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-body-text/60">
          <span className="h-px flex-1 bg-black/10" />
          {labels.orPayWithCard}
          <span className="h-px flex-1 bg-black/10" />
        </div>
      ) : null}
      {!elementReady ? (
        <div className="space-y-3">
          <Skeleton className="h-10 w-full" />
          <Skeleton className="h-10 w-full" />
        </div>
      ) : null}
      <PaymentElement onReady={() => setElementReady(true)} />
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <button
          type="button"
          onClick={onBack}
          className="text-sm font-medium text-body-text hover:text-ink"
        >
          {labels.backToGift}
        </button>
        <SiteButton
          type="submit"
          variant="primary"
          className="w-full sm:w-auto"
          disabled={processing || !stripe || !elements}
          ariaLabel={`${labels.donateAmount} ${formatUsd(chargeCents)}${isRecurring ? labels.perMonthSuffix : ""}`}
        >
          {processing ? (
            <span className="inline-flex items-center gap-2">
              <Spinner className="size-4" />
              {labels.processing}
            </span>
          ) : (
            `${labels.donateAmount} ${formatUsd(chargeCents)}${isRecurring ? labels.perMonthSuffix : ""}`
          )}
        </SiteButton>
      </div>
    </form>
  );
}

function errorBanner(message: string | null) {
  if (!message) {
    return null;
  }

  return <p className="text-sm text-action-red" role="alert">{message}</p>;
}

export function DonationCheckout({
  campaign,
  isGeneral,
  minAmountCents,
  publishableKey,
  feeConfig,
  countries,
  t,
  disabled = false,
  goalReached = false,
}: DonationCheckoutProps) {
  const labels = t.donatePage;

  const [frequency, setFrequency] = useState<RecurrenceFrequency>("one_time");
  const [amountCents, setAmountCents] = useState(5000);
  const [customAmount, setCustomAmount] = useState("");
  const [allocationRows, setAllocationRows] = useState<AllocationRow[]>([]);
  const [campaignOptions, setCampaignOptions] = useState<CampaignOption[]>([]);
  const [campaignsLoading, setCampaignsLoading] = useState(false);
  const [campaignsLoaded, setCampaignsLoaded] = useState(false);
  const [donorCoversFee, setDonorCoversFee] = useState(false);
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [countryId, setCountryId] = useState("");
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [donorMessage, setDonorMessage] = useState("");
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const [paymentIntentId, setPaymentIntentId] = useState<string | null>(null);
  const [stripePromise, setStripePromise] = useState<Promise<Stripe | null> | null>(
    null,
  );
  const [breakdown, setBreakdown] = useState<BreakdownSnapshot | null>(null);
  const [fieldErrors, setFieldErrors] = useState<DonorFieldErrors>({});
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const isRecurring = frequency !== "one_time";

  const resolvedAmountCents = customAmount
    ? dollarsToCents(customAmount) ?? 0
    : amountCents;

  const allocationTotalCents = allocationRows.reduce((sum, row) => {
    const cents = Math.round((Number.parseFloat(row.amount) || 0) * 100);
    return sum + (Number.isFinite(cents) ? cents : 0);
  }, 0);

  const chargeAmountCents = isRecurring ? allocationTotalCents : resolvedAmountCents;

  const liveBreakdown = useMemo(
    () =>
      calculateDonationBreakdown(chargeAmountCents, donorCoversFee, feeConfig),
    [chargeAmountCents, donorCoversFee, feeConfig],
  );

  // Prefill the allocation editor with the current campaign (or the general
  // fund) the first time the donor switches to a recurring frequency.
  useEffect(() => {
    if (isRecurring && allocationRows.length === 0) {
      const defaultTarget = isGeneral || !campaign ? "general" : campaign.id;
      const defaultAmount = resolvedAmountCents > 0 ? String(resolvedAmountCents / 100) : "";
      setAllocationRows([createAllocationRow(defaultTarget, defaultAmount)]);
    }
  }, [isRecurring, allocationRows.length, isGeneral, campaign, resolvedAmountCents]);

  // Lazily fetch the donatable campaigns list the first time it is needed
  // for the allocation picker.
  useEffect(() => {
    if (!isRecurring || campaignsLoaded || campaignsLoading) {
      return;
    }

    setCampaignsLoading(true);

    fetch(route("donations.campaigns-list"), {
      headers: { Accept: "application/json" },
    })
      .then((response) => (response.ok ? response.json() : []))
      .then((data: CampaignOption[]) => {
        setCampaignOptions(Array.isArray(data) ? data : []);
        setCampaignsLoaded(true);
      })
      .catch(() => {
        setCampaignsLoaded(true);
      })
      .finally(() => setCampaignsLoading(false));
  }, [isRecurring, campaignsLoaded, campaignsLoading]);

  const allocationsValid =
    allocationRows.length > 0 &&
    allocationRows.every((row) => {
      const cents = Math.round((Number.parseFloat(row.amount) || 0) * 100);
      return cents >= minAmountCents;
    });

  const isEmailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
  const stepOneValid =
    (isRecurring ? allocationsValid : resolvedAmountCents >= minAmountCents) &&
    firstName.trim().length > 0 &&
    lastName.trim().length > 0 &&
    isEmailValid &&
    !disabled &&
    !goalReached;

  const resetPaymentStep = () => {
    setClientSecret(null);
    setPaymentIntentId(null);
    setStripePromise(null);
    setBreakdown(null);
    setError(null);
  };

  const startCheckout = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);
    setFieldErrors({});

    if (isRecurring ? !allocationsValid : resolvedAmountCents < minAmountCents) {
      setError(`${labels.minAmountError} ${formatUsd(minAmountCents)}.`);
      return;
    }

    setLoading(true);

    try {
      const token =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

      const donorFields = {
        donor_covers_fee: donorCoversFee,
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim() || null,
        country_id: countryId ? Number(countryId) : null,
        is_anonymous: isAnonymous,
        donor_message: donorMessage.trim() || null,
      };

      const requestBody = isRecurring
        ? {
            frequency,
            allocations: allocationRows.map((row) => ({
              campaign_id: row.target === "general" ? null : row.target,
              is_general: row.target === "general",
              amount: Math.round((Number.parseFloat(row.amount) || 0) * 100),
            })),
            ...donorFields,
          }
        : {
            campaign_id: isGeneral ? null : campaign?.id,
            is_general: isGeneral,
            amount: resolvedAmountCents,
            ...donorFields,
          };

      const response = await fetch(
        route(isRecurring ? "donations.subscribe" : "donations.intent"),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": token,
          },
          body: JSON.stringify(requestBody),
        },
      );

      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        if (payload?.errors) {
          const mapped: DonorFieldErrors = {};
          Object.entries(payload.errors).forEach(([key, value]) => {
            if (Array.isArray(value) && value[0]) {
              mapped[key as keyof DonorFieldErrors] = value[0];
            }
          });
          setFieldErrors(mapped);
        }

        throw new Error(payload?.message ?? labels.paymentError);
      }

      setClientSecret(payload.clientSecret);
      setPaymentIntentId(payload.paymentIntentId);
      setBreakdown({
        amountCents: payload.amount ?? payload.breakdown?.amountCents ?? chargeAmountCents,
        chargeCents: payload.chargeCents ?? payload.breakdown?.chargeCents ?? liveBreakdown.chargeCents,
        estimatedFeeCents:
          payload.estimatedFee ?? payload.breakdown?.estimatedFeeCents ?? liveBreakdown.estimatedFeeCents,
      });
      setStripePromise(loadStripe(payload.publishableKey ?? publishableKey));
    } catch (checkoutError) {
      setError(
        checkoutError instanceof Error ? checkoutError.message : labels.paymentError,
      );
    } finally {
      setLoading(false);
    }
  };

  if (clientSecret && paymentIntentId && stripePromise && breakdown) {
    return (
      <div className="space-y-6">
        <div className="rounded-xl bg-surface-soft p-4 text-sm text-body-text ring-1 ring-black/5">
          <p>
            {labels.breakdownGift} {formatUsd(breakdown.amountCents)} ·{" "}
            {labels.breakdownFee} {formatUsd(breakdown.estimatedFeeCents)} ·{" "}
            <span className="font-semibold text-ink">
              {labels.breakdownTotal} {formatUsd(breakdown.chargeCents)}
            </span>
          </p>
        </div>
        <Elements stripe={stripePromise} options={{ clientSecret }}>
          <PaymentStep
            paymentIntentId={paymentIntentId}
            chargeCents={breakdown.chargeCents}
            isRecurring={isRecurring}
            labels={labels}
            onError={setError}
            onBack={resetPaymentStep}
          />
        </Elements>
        {errorBanner(error)}
      </div>
    );
  }

  return (
    <form onSubmit={startCheckout} className="space-y-6">
      <div>
        <span className="mb-2 block text-sm font-medium text-ink">
          {labels.frequencyLabel}
        </span>
        <div className="inline-flex flex-wrap gap-1 rounded-lg bg-surface-soft p-1 ring-1 ring-black/5">
          {(
            [
              ["one_time", labels.frequencyOneTime],
              ["weekly", labels.frequencyWeekly],
              ["monthly", labels.frequencyMonthly],
              ["quarterly", labels.frequencyQuarterly],
              ["yearly", labels.frequencyYearly],
            ] as [RecurrenceFrequency, string][]
          ).map(([value, label]) => (
            <button
              key={value}
              type="button"
              onClick={() => setFrequency(value)}
              aria-pressed={frequency === value}
              className={frequency === value ? frequencyButtonActiveClass : frequencyButtonInactiveClass}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {isRecurring ? (
        <AllocationEditor
          rows={allocationRows}
          campaigns={campaignOptions}
          campaignsLoading={campaignsLoading}
          minAmountCents={minAmountCents}
          labels={{
            allocationTarget: labels.allocationTarget,
            allocationAmount: labels.allocationAmount,
            generalFundOption: labels.generalFundOption,
            addCampaignAllocation: labels.addCampaignAllocation,
            removeAllocation: labels.removeAllocation,
            totalPerCycle: labels.totalPerCycle,
            allocationAmountError: labels.allocationAmountError,
            loadingCampaigns: labels.loadingCampaigns,
          }}
          onRowsChange={setAllocationRows}
        />
      ) : (
        <AmountChips
          amountCents={amountCents}
          customAmount={customAmount}
          minAmountCents={minAmountCents}
          labels={{
            chooseAmount: labels.chooseAmount,
            customAmount: labels.customAmount,
            customAmountPlaceholder: labels.customAmountPlaceholder,
          }}
          onPresetSelect={(preset) => {
            setAmountCents(preset);
            setCustomAmount("");
          }}
          onCustomAmountChange={setCustomAmount}
        />
      )}

      <FeeToggle
        checked={donorCoversFee}
        onCheckedChange={setDonorCoversFee}
        amountCents={chargeAmountCents}
        feeConfig={feeConfig}
        label={labels.coverFeeLabel}
        labels={{
          breakdownGift: labels.breakdownGift,
          breakdownFee: labels.breakdownFee,
          breakdownTotal: labels.breakdownTotal,
          breakdownFeeNote: labels.breakdownFeeNote,
        }}
      />

      <DonorFields
        firstName={firstName}
        lastName={lastName}
        email={email}
        phone={phone}
        countryId={countryId}
        isAnonymous={isAnonymous}
        donorMessage={donorMessage}
        countries={countries}
        errors={fieldErrors}
        labels={{
          firstName: labels.firstName,
          lastName: labels.lastName,
          email: labels.email,
          phone: labels.phone,
          country: labels.country,
          countryPlaceholder: labels.countryPlaceholder,
          anonymousLabel: labels.anonymousLabel,
          message: labels.message,
          messagePlaceholder: labels.messagePlaceholder,
        }}
        onFirstNameChange={setFirstName}
        onLastNameChange={setLastName}
        onEmailChange={setEmail}
        onPhoneChange={setPhone}
        onCountryChange={setCountryId}
        onAnonymousChange={setIsAnonymous}
        onMessageChange={setDonorMessage}
      />

      {errorBanner(error)}

      <SiteButton
        type="submit"
        variant="primary"
        className="w-full"
        disabled={!stepOneValid || loading}
        ariaLabel={labels.continueToPayment}
      >
        {loading ? labels.preparingCheckout : labels.continueToPayment}
      </SiteButton>
    </form>
  );
}

export default DonationCheckout;
