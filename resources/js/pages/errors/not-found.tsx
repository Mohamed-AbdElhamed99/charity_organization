import { Head } from "@inertiajs/react";
import { ArrowRight, Compass } from "lucide-react";
import { route } from "ziggy-js";
import { SiteButton } from "@/components/site/site-button";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";

export default function NotFound() {
  const { t, dir } = useLocale();

  return (
    <>
      <Head title={t.notFoundPage.title} />

      <section className="flex min-h-[70vh] items-center bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-2xl px-6 text-center" dir={dir}>
          <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm">
            <Compass className="h-8 w-8 text-gold" />
          </div>

          <p className="text-xs font-semibold uppercase tracking-widest text-gold">
            {t.notFoundPage.eyebrow}
          </p>
          <h1 className="mt-3 font-display text-4xl font-extrabold text-ink">
            {t.notFoundPage.title}
          </h1>
          <p className="mt-4 text-body-text">{t.notFoundPage.body}</p>

          <div className="mt-8 flex justify-center">
            <SiteButton href={route("home")} icon={<ArrowRight className="h-4 w-4" />}>
              {t.notFoundPage.cta}
            </SiteButton>
          </div>
        </div>
      </section>
    </>
  );
}

NotFound.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
