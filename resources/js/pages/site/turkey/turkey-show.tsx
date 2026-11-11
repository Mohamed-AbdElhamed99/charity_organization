import { Head, useForm } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import InputError from "@/components/input-error";
import { useLocale } from "@/context/locale-context";
import { SiteLayout } from "@/layouts/site-layout";

type PageProps = {
  remaining: number;
  total: number;
  whatsappUrl: string | null;
};

const inputClass =
  "rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red focus-visible:ring-2 focus-visible:ring-action-red/30";

export default function TurkeyShow({ remaining, total, whatsappUrl }: PageProps) {
  const { t, dir } = useLocale();
  const copy = t.turkeyPage;
  const [submitted, setSubmitted] = useState(false);

  const form = useForm({
    full_name: "",
    email: "",
    phone: "",
    address: "",
    _hp: "",
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("turkey.store"), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        setSubmitted(true);
      },
    });
  };

  const isSoldOut = remaining <= 0;

  return (
    <>
      <Head title={copy.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-2xl px-6">
          <p className="text-xs font-semibold uppercase tracking-widest text-gold">
            {copy.eyebrow}
          </p>
          <h1 className="mt-3 font-display text-4xl font-extrabold text-ink">
            {copy.title}
          </h1>
          <p className="mt-4 text-body-text">{copy.subtitle}</p>

          <div
            className="mt-6 inline-flex items-baseline gap-2 rounded-full bg-action-red px-6 py-3 text-white"
            role="status"
            aria-live="polite"
          >
            <span className="text-sm font-semibold uppercase tracking-wide">
              {copy.remainingLabel}
            </span>
            <span className="text-3xl font-extrabold">{remaining}</span>
            <span className="text-sm">
              {copy.remainingOf.replace("{total}", String(total))}
            </span>
          </div>

          {submitted ? (
            <div
              className="mt-10 rounded-2xl border border-surface-soft bg-white p-8"
              role="status"
            >
              <h2 className="font-display text-2xl font-bold text-ink">
                {copy.successTitle}
              </h2>
              <p className="mt-3 text-body-text">{copy.successBody}</p>
            </div>
          ) : isSoldOut ? (
            <div
              className="mt-10 rounded-2xl border border-surface-soft bg-white p-8"
              role="status"
            >
              <h2 className="font-display text-2xl font-bold text-ink">
                {copy.soldOutTitle}
              </h2>
              <p className="mt-3 text-body-text">{copy.soldOutBody}</p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="mt-10 space-y-5" noValidate>
              <input
                type="text"
                name="_hp"
                value={form.data._hp}
                onChange={(event) => form.setData("_hp", event.target.value)}
                className="hidden"
                tabIndex={-1}
                autoComplete="off"
                aria-hidden="true"
              />

              <div className="grid gap-2">
                <label htmlFor="full_name" className="text-sm font-medium text-ink">
                  {copy.fullname}
                </label>
                <input
                  id="full_name"
                  value={form.data.full_name}
                  onChange={(event) => form.setData("full_name", event.target.value)}
                  required
                  autoComplete="name"
                  dir={dir}
                  className={inputClass}
                />
                <InputError message={form.errors.full_name} />
              </div>

              <div className="grid gap-2">
                <label htmlFor="email" className="text-sm font-medium text-ink">
                  {copy.email}
                </label>
                <input
                  id="email"
                  type="email"
                  value={form.data.email}
                  onChange={(event) => form.setData("email", event.target.value)}
                  required
                  autoComplete="email"
                  dir="ltr"
                  className={inputClass}
                />
                <InputError message={form.errors.email} />
              </div>

              <div className="grid gap-2">
                <label htmlFor="phone" className="text-sm font-medium text-ink">
                  {copy.phone}
                </label>
                <input
                  id="phone"
                  type="tel"
                  value={form.data.phone}
                  onChange={(event) => form.setData("phone", event.target.value)}
                  required
                  autoComplete="tel"
                  dir="ltr"
                  className={inputClass}
                />
                <InputError message={form.errors.phone} />
              </div>

              <div className="grid gap-2">
                <label htmlFor="address" className="text-sm font-medium text-ink">
                  {copy.address}
                </label>
                <textarea
                  id="address"
                  value={form.data.address}
                  onChange={(event) => form.setData("address", event.target.value)}
                  required
                  rows={3}
                  autoComplete="street-address"
                  dir={dir}
                  className={inputClass}
                />
                <InputError message={form.errors.address} />
              </div>

              <button
                type="submit"
                disabled={form.processing}
                className="inline-flex items-center justify-center rounded-full bg-action-red px-8 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action-red"
              >
                {form.processing ? copy.submitting : copy.submit}
              </button>
            </form>
          )}

          <p className="mt-8 text-sm text-body-text">
            {whatsappUrl ? (
              <a
                href={whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="font-semibold text-action-red underline"
              >
                {copy.whatsappCta}
              </a>
            ) : null}{" "}
            {copy.contactNote}
          </p>
        </div>
      </section>
    </>
  );
}

TurkeyShow.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
