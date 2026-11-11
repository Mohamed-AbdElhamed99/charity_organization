import { Head, useForm } from "@inertiajs/react";
import { useState } from "react";
import { route } from "ziggy-js";
import InputError from "@/components/input-error";
import { useLocale } from "@/context/locale-context";
import { SiteLayout } from "@/layouts/site-layout";

export default function TurkeyVerify() {
  const { t } = useLocale();
  const copy = t.turkeyPage;
  const [verified, setVerified] = useState(false);

  const form = useForm({ code: "" });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("turkey.verify.store"), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        setVerified(true);
      },
    });
  };

  return (
    <>
      <Head title={copy.verifyTitle} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-md px-6">
          <p className="text-xs font-semibold uppercase tracking-widest text-gold">
            {copy.eyebrow}
          </p>
          <h1 className="mt-3 font-display text-3xl font-extrabold text-ink">
            {copy.verifyTitle}
          </h1>

          {verified ? (
            <div
              className="mt-8 rounded-2xl border border-surface-soft bg-white p-8"
              role="status"
            >
              <h2 className="font-display text-2xl font-bold text-ink">
                {copy.verifySuccessTitle}
              </h2>
              <p className="mt-3 text-body-text">{copy.verifySuccessBody}</p>
            </div>
          ) : (
            <>
              <p className="mt-4 text-body-text">{copy.verifyIntro}</p>

              <form onSubmit={handleSubmit} className="mt-8 space-y-5" noValidate>
                <div className="grid gap-2">
                  <label htmlFor="code" className="text-sm font-medium text-ink">
                    {copy.codeLabel}
                  </label>
                  <input
                    id="code"
                    value={form.data.code}
                    onChange={(event) =>
                      form.setData("code", event.target.value.toUpperCase())
                    }
                    placeholder={copy.codePlaceholder}
                    required
                    autoComplete="off"
                    autoCapitalize="characters"
                    dir="ltr"
                    className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-center text-lg font-semibold tracking-widest outline-none focus:border-action-red focus-visible:ring-2 focus-visible:ring-action-red/30"
                  />
                  <InputError message={form.errors.code} />
                </div>

                <button
                  type="submit"
                  disabled={form.processing}
                  className="inline-flex w-full items-center justify-center rounded-full bg-action-red px-8 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action-red"
                >
                  {form.processing ? copy.submitting : copy.verifySubmit}
                </button>
              </form>
            </>
          )}
        </div>
      </section>
    </>
  );
}

TurkeyVerify.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
