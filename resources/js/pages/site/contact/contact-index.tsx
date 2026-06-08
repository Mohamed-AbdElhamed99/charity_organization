import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import InputError from "@/components/input-error";

export default function ContactIndex() {
  const { t, dir } = useLocale();

  const form = useForm({
    fullname: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
    _hp: "",
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(route("contact.store"), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  return (
    <>
      <Head title={t.contactPage.pageTitle} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-2xl px-6">
          <p className="text-xs font-semibold uppercase tracking-widest text-gold">
            {t.contactPage.eyebrow}
          </p>
          <h1 className="mt-3 font-display text-4xl font-extrabold text-ink">
            {t.contactPage.pageTitle}
          </h1>
          <p className="mt-4 text-body-text">{t.contactPage.pageIntro}</p>

          <form onSubmit={handleSubmit} className="mt-10 space-y-5">
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
              <label htmlFor="fullname" className="text-sm font-medium text-ink">
                {t.contactPage.fullname}
              </label>
              <input
                id="fullname"
                value={form.data.fullname}
                onChange={(event) =>
                  form.setData("fullname", event.target.value)
                }
                required
                dir={dir}
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.fullname} />
            </div>

            <div className="grid gap-2">
              <label htmlFor="email" className="text-sm font-medium text-ink">
                {t.contactPage.email}
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
                {t.contactPage.phone}
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
              <label htmlFor="subject" className="text-sm font-medium text-ink">
                {t.contactPage.subject}
              </label>
              <input
                id="subject"
                value={form.data.subject}
                onChange={(event) =>
                  form.setData("subject", event.target.value)
                }
                required
                dir={dir}
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.subject} />
            </div>

            <div className="grid gap-2">
              <label htmlFor="message" className="text-sm font-medium text-ink">
                {t.contactPage.message}
              </label>
              <textarea
                id="message"
                value={form.data.message}
                onChange={(event) =>
                  form.setData("message", event.target.value)
                }
                required
                rows={6}
                dir={dir}
                className="rounded-lg border border-surface-soft bg-white px-4 py-3 text-sm outline-none focus:border-action-red"
              />
              <InputError message={form.errors.message} />
            </div>

            <button
              type="submit"
              disabled={form.processing}
              className="inline-flex items-center justify-center rounded-full bg-action-red px-8 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {t.contactPage.submit}
            </button>
          </form>
        </div>
      </section>
    </>
  );
}

ContactIndex.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
