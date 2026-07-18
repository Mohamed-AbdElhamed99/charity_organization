import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import InputError from "@/components/input-error";

type ProfileData = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  email_verified: boolean;
};

export default function AccountProfile({
  profile,
  status,
}: {
  profile: ProfileData;
  status?: string;
}) {
  const { t, dir } = useLocale();
  const i18n = t.accountPage.profile;

  const form = useForm({
    first_name: profile.first_name,
    last_name: profile.last_name,
    email: profile.email,
    phone: profile.phone ?? "",
    password: "",
    password_confirmation: "",
    current_password: "",
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.patch(route("account.profile.update"), {
      onSuccess: () => form.setData((data) => ({
        ...data,
        password: "",
        password_confirmation: "",
        current_password: "",
      })),
    });
  };

  return (
    <>
      <Head title={i18n.title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-md px-6">
          <h1 className="font-display text-3xl font-extrabold text-ink">{i18n.title}</h1>

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

            <hr className="border-surface-soft" />

            <div className="grid gap-2">
              <label htmlFor="password" className="text-sm font-medium text-ink">
                {i18n.newPassword}
              </label>
              <input
                id="password"
                type="password"
                value={form.data.password}
                onChange={(event) => form.setData("password", event.target.value)}
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.password} />
            </div>

            <div className="grid gap-2">
              <label htmlFor="password_confirmation" className="text-sm font-medium text-ink">
                {i18n.newPasswordConfirmation}
              </label>
              <input
                id="password_confirmation"
                type="password"
                value={form.data.password_confirmation}
                onChange={(event) => form.setData("password_confirmation", event.target.value)}
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
            </div>

            <div className="grid gap-2">
              <label htmlFor="current_password" className="text-sm font-medium text-ink">
                {i18n.currentPassword}
              </label>
              <input
                id="current_password"
                type="password"
                value={form.data.current_password}
                onChange={(event) => form.setData("current_password", event.target.value)}
                dir="ltr"
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.current_password} />
            </div>

            <button
              type="submit"
              disabled={form.processing}
              className="inline-flex w-full items-center justify-center rounded-full bg-action-red px-8 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {i18n.save}
            </button>
          </form>
        </div>
      </section>
    </>
  );
}

AccountProfile.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
