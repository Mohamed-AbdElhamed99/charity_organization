import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import InputError from "@/components/input-error";

export default function AccountForgotPassword({ status }: { status?: string }) {
  const { t } = useLocale();
  const i18n = t.accountPage.forgotPassword;

  const form = useForm({ email: "" });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("account.password.email"));
  };

  return (
    <>
      <Head title={i18n.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-md px-6">
          <h1 className="font-display text-3xl font-extrabold text-ink">
            {i18n.title}
          </h1>
          <p className="mt-3 text-body-text">{i18n.intro}</p>

          {status && (
            <p className="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              {status}
            </p>
          )}

          <form onSubmit={handleSubmit} className="mt-8 space-y-5">
            <div className="grid gap-2">
              <label htmlFor="email" className="text-sm font-medium text-ink">
                {i18n.email}
              </label>
              <input
                id="email"
                type="email"
                value={form.data.email}
                onChange={(event) => form.setData("email", event.target.value)}
                required
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.email} />
            </div>

            <button
              type="submit"
              disabled={form.processing}
              className="inline-flex w-full items-center justify-center rounded-full bg-action-red px-8 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {i18n.submit}
            </button>
          </form>
        </div>
      </section>
    </>
  );
}

AccountForgotPassword.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
