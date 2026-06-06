import { useCallback, useRef, useState } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft, ArrowRight, Calendar, ChevronLeft, ChevronRight, Play } from "lucide-react";
import { SiteLayout } from "@/layouts/site-layout";
import { useLocale } from "@/context/locale-context";
import { cn } from "@/lib/utils";

interface GalleryItem {
  id: number;
  url: string;
  mime_type: string;
}

interface Article {
  id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  excerpt: string | null;
  body: string | null;
  meta_title: string | null;
  meta_description: string | null;
  category_id: number | null;
  category_name: string | null;
  thumbnail: string;
  main_media: string;
  main_media_type: string | null;
  gallery: GalleryItem[];
  published_at: string | null;
  video_url: string | null;
}

type PageProps = {
  article: Article;
};

function isVideoMime(mimeType: string | null): boolean {
  return !!mimeType && mimeType.startsWith("video/");
}

function MainMedia({ url, mimeType }: { url: string; mimeType: string | null }) {
  if (isVideoMime(mimeType)) {
    return (
      <video
        src={url}
        controls
        playsInline
        className="h-full w-full object-cover"
        aria-label="Article video"
      />
    );
  }
  return (
    <img
      src={url}
      alt="Article cover"
      className="h-full w-full object-cover"
      loading="eager"
    />
  );
}

function GallerySlider({ items }: { items: GalleryItem[] }) {
  const [current, setCurrent] = useState(0);
  const videoRefs = useRef<Record<number, HTMLVideoElement | null>>({});

  const pauseVideo = (index: number) => {
    videoRefs.current[index]?.pause();
  };

  const go = useCallback(
    (next: number) => {
      pauseVideo(current);
      setCurrent(next);
    },
    [current]
  );

  const prev = () => go((current - 1 + items.length) % items.length);
  const next = () => go((current + 1) % items.length);

  if (items.length === 0) {
    return null;
  }

  const active = items[current];

  return (
    <div className="mt-10">
      {/* Main slide */}
      <div className="relative overflow-hidden rounded-2xl bg-black aspect-video">
        {isVideoMime(active.mime_type) ? (
          <video
            ref={(el) => { videoRefs.current[current] = el; }}
            key={active.id}
            src={active.url}
            controls
            playsInline
            className="h-full w-full object-contain"
            aria-label={`Gallery item ${current + 1}`}
          />
        ) : (
          <img
            key={active.id}
            src={active.url}
            alt={`Gallery item ${current + 1}`}
            className="h-full w-full object-contain"
            loading="lazy"
          />
        )}

        {items.length > 1 && (
          <>
            <button
              onClick={prev}
              aria-label="Previous slide"
              className="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm transition hover:bg-black/75"
            >
              <ChevronLeft className="h-5 w-5 rtl:-scale-x-100" />
            </button>
            <button
              onClick={next}
              aria-label="Next slide"
              className="absolute top-1/2 -translate-y-1/2 ltr:right-3 rtl:left-3 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white backdrop-blur-sm transition hover:bg-black/75"
            >
              <ChevronRight className="h-5 w-5 rtl:-scale-x-100" />
            </button>
          </>
        )}

        {/* Counter */}
        <span className="absolute bottom-3 ltr:right-3 rtl:left-3 rounded-full bg-black/50 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm">
          {current + 1} / {items.length}
        </span>
      </div>

      {/* Thumbnails strip */}
      {items.length > 1 && (
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
          {items.map((item, index) => (
            <button
              key={item.id}
              onClick={() => go(index)}
              aria-label={`Go to slide ${index + 1}`}
              className={cn(
                "relative h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 transition",
                index === current
                  ? "border-action-red"
                  : "border-transparent opacity-60 hover:opacity-100"
              )}
            >
              {isVideoMime(item.mime_type) ? (
                <div className="flex h-full w-full items-center justify-center bg-black">
                  <Play className="h-5 w-5 text-white" />
                </div>
              ) : (
                <img
                  src={item.url}
                  alt={`Thumbnail ${index + 1}`}
                  className="h-full w-full object-cover"
                  loading="lazy"
                />
              )}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

export default function NewsShow() {
  const { t, locale, dir } = useLocale();
  const { article } = usePage<PageProps>().props;
  const { url } = usePage();

  const BackArrow = dir === "rtl" ? ArrowRight : ArrowLeft;

  const metaTitle = article.meta_title || article.title;
  const metaDescription = article.meta_description || article.excerpt || "";
  const metaImage = article.thumbnail || article.main_media || "";

  return (
    <>
      <Head title={metaTitle}>
        <meta name="description" content={metaDescription} />

        {/* Open Graph */}
        <meta property="og:type" content="article" />
        <meta property="og:title" content={metaTitle} />
        <meta property="og:description" content={metaDescription} />
        {metaImage && <meta property="og:image" content={metaImage} />}
        <meta property="og:url" content={url} />
        {article.published_at && (
          <meta
            property="article:published_time"
            content={article.published_at}
          />
        )}

        {/* Twitter Card */}
        <meta name="twitter:card" content={metaImage ? "summary_large_image" : "summary"} />
        <meta name="twitter:title" content={metaTitle} />
        <meta name="twitter:description" content={metaDescription} />
        {metaImage && <meta name="twitter:image" content={metaImage} />}
      </Head>

      {/* Hero section */}
      <section className="relative bg-ink text-white pt-28 pb-0 md:pt-36">
        <div className="mx-auto max-w-[900px] px-6">
          {/* Breadcrumb */}
          <Link
            href="/news"
            className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-white/60 hover:text-white transition"
          >
            <BackArrow className="h-4 w-4" />
            {t.newsPage.backToNews}
          </Link>

          {article.category_name && (
            <span className="mb-4 block text-xs font-semibold uppercase tracking-widest text-gold">
              {article.category_name}
            </span>
          )}

          <h1 className="font-display text-3xl font-bold leading-tight text-white md:text-4xl lg:text-5xl">
            {article.title}
          </h1>

          {article.published_at && (
            <div className="mt-4 flex items-center gap-2 text-sm text-white/60">
              <Calendar className="h-4 w-4" />
              <time dateTime={article.published_at}>
                {t.newsPage.publishedOn}: {article.published_at}
              </time>
            </div>
          )}
        </div>

        {/* Main media */}
        {article.main_media && (
          <div className="mx-auto mt-8 max-w-[900px] px-6">
            <div className="overflow-hidden rounded-t-2xl aspect-[16/9] shadow-2xl">
              <MainMedia url={article.main_media} mimeType={article.main_media_type} />
            </div>
          </div>
        )}
      </section>

      {/* Article body */}
      <article className="bg-white py-12 md:py-16">
        <div className="mx-auto max-w-[720px] px-6">
          {article.subtitle && (
            <p className="mb-8 text-xl font-semibold text-ink leading-relaxed">
              {article.subtitle}
            </p>
          )}

          {article.body && (
            <div
              dir={dir}
              className="prose prose-neutral max-w-none prose-headings:font-display prose-headings:text-ink prose-a:text-action-red prose-a:underline"
              dangerouslySetInnerHTML={{ __html: article.body }}
            />
          )}

          {/* Gallery */}
          {article.gallery.length > 0 && (
            <section className="mt-16">
              <h2 className="mb-6 font-display text-2xl font-bold text-ink">
                {locale === "ar" ? "معرض الصور" : "Gallery"}
              </h2>
              <GallerySlider items={article.gallery} />
            </section>
          )}

          {/* Back link */}
          <div className="mt-16 border-t border-black/5 pt-8">
            <Link
              href="/news"
              className="inline-flex items-center gap-2 text-sm font-semibold text-action-red hover:underline"
            >
              <BackArrow className="h-4 w-4" />
              {t.newsPage.backToNews}
            </Link>
          </div>
        </div>
      </article>
    </>
  );
}

NewsShow.layout = (page: React.ReactNode) => <SiteLayout transparentHeader={false}>{page}</SiteLayout>;
