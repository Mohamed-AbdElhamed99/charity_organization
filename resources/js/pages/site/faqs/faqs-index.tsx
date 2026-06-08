import { Head, usePage } from "@inertiajs/react";
import { ChevronDown } from "lucide-react";
import { useState } from "react";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { cn } from "@/lib/utils";
import type { SiteFaq } from "@/types/models/faq";

type PageProps = {
  faqs: SiteFaq[];
};

function FaqItem({ faq, dir }: { faq: SiteFaq; dir: "ltr" | "rtl" }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="rounded-xl border border-surface-soft bg-white">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        className="flex w-full items-center justify-between gap-4 px-6 py-5 text-start"
        aria-expanded={open}
      >
        <span className="font-display text-lg font-semibold text-ink" dir={dir}>
          {faq.question}
        </span>
        <ChevronDown
          className={cn(
            "h-5 w-5 shrink-0 text-gold transition-transform",
            open && "rotate-180"
          )}
        />
      </button>
      {open && (
        <div
          className="border-t border-surface-soft px-6 py-5 text-body-text leading-relaxed"
          dir={dir}
          dangerouslySetInnerHTML={{ __html: faq.answer }}
        />
      )}
    </div>
  );
}

export default function FaqsIndex() {
  const { faqs } = usePage<PageProps>().props;
  const { t, dir } = useLocale();

  return (
    <>
      <Head title={t.faqsPage.pageTitle} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-3xl px-6">
          <p className="text-xs font-semibold uppercase tracking-widest text-gold">
            {t.faqsPage.eyebrow}
          </p>
          <h1 className="mt-3 font-display text-4xl font-extrabold text-ink">
            {t.faqsPage.pageTitle}
          </h1>
          <p className="mt-4 text-body-text">{t.faqsPage.pageIntro}</p>

          <div className="mt-10 space-y-4">
            {faqs.length > 0 ? (
              faqs.map((faq) => (
                <FaqItem key={faq.id} faq={faq} dir={dir} />
              ))
            ) : (
              <p className="text-center text-muted-foreground">
                {t.faqsPage.noResults}
              </p>
            )}
          </div>
        </div>
      </section>
    </>
  );
}

FaqsIndex.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
