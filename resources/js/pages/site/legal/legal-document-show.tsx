import { Head, usePage } from "@inertiajs/react";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import type { SiteLegalDocument } from "@/types/models/legal-document";

type PageProps = {
  document: SiteLegalDocument;
};

export default function LegalDocumentShow() {
  const { document } = usePage<PageProps>().props;
  const { dir } = useLocale();

  return (
    <>
      <Head title={document.meta_title} />

      <section className="bg-surface pb-20 pt-32">
        <div className="mx-auto max-w-3xl px-6">
          <h1
            className="font-display text-4xl font-extrabold text-ink"
            dir={dir}
          >
            {document.title}
          </h1>
          {document.updated_at && (
            <p className="mt-3 text-sm text-muted-foreground">
              {document.updated_at}
            </p>
          )}
          <div
            className="prose prose-sm md:prose-base lg:prose-lg prose-neutral mt-10 max-w-none prose-headings:font-display prose-headings:text-ink prose-a:text-action-red prose-a:underline leading-relaxed"
            dir={dir}
            dangerouslySetInnerHTML={{ __html: document.body }}
          />
        </div>
      </section>
    </>
  );
}

LegalDocumentShow.layout = (page: React.ReactNode) => (
  <SiteLayout transparentHeader={false}>{page}</SiteLayout>
);
