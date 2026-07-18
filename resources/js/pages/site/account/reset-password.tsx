import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import InputError from "@/components/input-error";

export default function AccountResetPassword({
  token,
  email,
}: {
  token: string;
  email: string;
}) {
  const { t } = useLocale();
  const i18n = t.accountPage.resetPassword;

  const form = useForm({
    token,
    email,
    password: "",
    password_confirmation: "",
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("account.password.update"));
  };

  return (
    <>
      <Head title={i18n.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-md px-6">
          <h1 className="font-display text-3xl font-extrabold text-ink">
            {i18n.title}
          </h1>

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

            <div className="grid gap-2">
              <label htmlFor="password" className="text-sm font-medium text-ink">
                {i18n.password}
              </label>
              <input
                id="password"
                type="password"
                value={form.data.password}
                onChange={(event) => form.setData("password", event.target.value)}
                required
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.password} />
            </div>

            <div className="grid gap-2">
              <label htmlFor="password_confirmation" className="text-sm font-medium text-ink">
                {i18n.passwordConfirmation}
              </label>
              <input
                id="password_confirmation"
                type="password"
                value={form.data.password_confirmation}
                onChange={(event) => form.setData("password_confirmation", event.target.value)}
                required
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
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

AccountResetPassword.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
