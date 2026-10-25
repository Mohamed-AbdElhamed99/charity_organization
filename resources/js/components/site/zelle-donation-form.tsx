import { useState } from "react";
import { route } from "ziggy-js";
import { DonorFields, type CountryOption, type DonorFieldErrors } from "@/components/site/donation-form/donor-fields";
import { SiteButton } from "@/components/site/site-button";
import type { SiteTranslations } from "@/lib/translations";

type ZelleDonationFormProps = {
  campaignId: number;
  appealId: number;
  amountCents: number;
  giftOption: number | null;
  minAmountCents: number;
  countries: CountryOption[];
  labels: SiteTranslations["donatePage"];
};

export function ZelleDonationForm({
  campaignId,
  appealId,
  amountCents,
  giftOption,
  minAmountCents,
  countries,
  labels,
}: ZelleDonationFormProps) {
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [countryId, setCountryId] = useState("");
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [donorMessage, setDonorMessage] = useState("");
  const [receipt, setReceipt] = useState<File | null>(null);
  const [fieldErrors, setFieldErrors] = useState<DonorFieldErrors>({});
  const [receiptError, setReceiptError] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);
    setFieldErrors({});
    setReceiptError(null);

    if (amountCents < minAmountCents) {
      setError(`${labels.minAmountError} ${minAmountCents / 100}.`);
      return;
    }

    if (!receipt) {
      setReceiptError(labels.zelleReceiptHint);
      return;
    }

    setSubmitting(true);

    try {
      const token =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
      const body = new FormData();
      body.append("campaign_id", String(campaignId));
      body.append("is_general", "0");
      body.append("amount", String(amountCents));
      body.append("appeal_id", String(appealId));
      if (giftOption !== null) {
        body.append("gift_option", String(giftOption));
      }
      body.append("first_name", firstName.trim());
      body.append("last_name", lastName.trim());
      body.append("email", email.trim());
      if (phone.trim()) {
        body.append("phone", phone.trim());
      }
      if (countryId) {
        body.append("country_id", countryId);
      }
      body.append("is_anonymous", isAnonymous ? "1" : "0");
      if (donorMessage.trim()) {
        body.append("donor_message", donorMessage.trim());
      }
      body.append("receipt", receipt);

      const response = await fetch(route("donations.zelle"), {
        method: "POST",
        headers: {
          Accept: "application/json",
          "X-CSRF-TOKEN": token,
        },
        body,
      });

      const payload = await response.json().catch(() => null);

      if (!response.ok) {
        if (payload?.errors) {
          const mapped: DonorFieldErrors = {};
          Object.entries(payload.errors).forEach(([key, value]) => {
            if (Array.isArray(value) && typeof value[0] === "string") {
              if (key === "receipt") {
                setReceiptError(value[0]);
                return;
              }
              mapped[key as keyof DonorFieldErrors] = value[0];
            }
          });
          setFieldErrors(mapped);
        }

        throw new Error(payload?.message ?? labels.paymentError);
      }

      if (typeof payload?.thankYouUrl === "string") {
        window.location.assign(payload.thankYouUrl);
        return;
      }

      setSubmitted(true);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : labels.paymentError);
    } finally {
      setSubmitting(false);
    }
  };

  if (submitted) {
    return (
      <p className="mt-4 rounded-xl bg-surface-soft p-4 text-sm leading-6 text-ink" role="status">
        {labels.zelleSubmitted}
      </p>
    );
  }

  return (
    <form onSubmit={submit} className="mt-4 space-y-4 text-start">
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
        labels={labels}
        onFirstNameChange={setFirstName}
        onLastNameChange={setLastName}
        onEmailChange={setEmail}
        onPhoneChange={setPhone}
        onCountryChange={setCountryId}
        onAnonymousChange={setIsAnonymous}
        onMessageChange={setDonorMessage}
      />

      <div>
        <label htmlFor="zelle-receipt" className="text-sm font-medium text-ink">
          {labels.zelleReceipt}
        </label>
        <input
          id="zelle-receipt"
          type="file"
          accept="image/jpeg,image/png,application/pdf"
          required
          onChange={(event) => setReceipt(event.target.files?.[0] ?? null)}
          className="mt-1 block w-full text-sm text-body-text file:me-3 file:rounded-full file:border-0 file:bg-action-red file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
        />
        <p className="mt-1 text-xs text-body-text/80">{labels.zelleReceiptHint}</p>
        {receiptError ? <p className="mt-1 text-sm text-action-red">{receiptError}</p> : null}
      </div>

      {error ? <p className="text-sm text-action-red">{error}</p> : null}

      <SiteButton type="submit" disabled={submitting || amountCents < minAmountCents} className="w-full">
        {submitting ? labels.zelleSubmitting : labels.zelleSubmit}
      </SiteButton>
    </form>
  );
}
