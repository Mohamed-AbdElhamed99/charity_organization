import { Head, Link, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import InputError from "@/components/input-error";

export default function AccountRegister({ status }: { status?: string }) {
  const { t, dir } = useLocale();
  const i18n = t.accountPage.register;

  const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("account.register"));
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
            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2">
                <label htmlFor="first_name" className="text-sm font-medium text-ink">
                  {i18n.firstName}
                </label>
                <input
                  id="first_name"
                  value={form.data.first_name}
                  onChange={(event) => form.setData("first_name", event.target.value)}
                  required
                  dir={dir}
                  className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
                />
                <InputError message={form.errors.first_name} />
              </div>

              <div className="grid gap-2">
                <label htmlFor="last_name" className="text-sm font-medium text-ink">
                  {i18n.lastName}
                </label>
                <input
                  id="last_name"
                  value={form.data.last_name}
                  onChange={(event) => form.setData("last_name", event.target.value)}
                  required
                  dir={dir}
                  className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
                />
                <InputError message={form.errors.last_name} />
              </div>
            </div>

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
              <label htmlFor="phone" className="text-sm font-medium text-ink">
                {i18n.phone}
              </label>
              <input
                id="phone"
                type="tel"
                value={form.data.phone}
                onChange={(event) => form.setData("phone", event.target.value)}
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.phone} />
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

            <p className="text-center text-sm text-body-text">
              {i18n.haveAccount}{" "}
              <Link href={route("account.login")} className="font-semibold text-action-red">
                {i18n.loginLink}
              </Link>
            </p>
          </form>
        </div>
      </section>
    </>
  );
}

AccountRegister.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
