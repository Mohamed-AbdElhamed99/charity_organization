import { useEffect, type ReactNode } from "react";
import { SiteHeader } from "@/components/site/site-header";
import { SiteFooter } from "@/components/site/site-footer";
import { LocaleProvider, useLocale } from "@/context/locale-context";

export interface SiteLayoutProps {
  children: ReactNode;
  transparentHeader?: boolean;
}

/** Inner shell — runs INSIDE the provider, so useLocale() is valid here. */
function SiteShell({
  children,
  transparentHeader = true,
}: SiteLayoutProps) {
  const { t, locale, setLocale, dir } = useLocale();

  // Keep <html> in sync (Blade sets it server-side; this covers client switches/preview).
  useEffect(() => {
    if (typeof document === "undefined") return;
    const html = document.documentElement;
    html.lang = locale;
    html.dir = dir;
  }, [locale, dir]);

  const fontClass = locale === "ar" ? "font-arabic" : "font-body";

  return (
    <div lang={locale} dir={dir} className={`bg-surface text-body-text ${fontClass}`}>
      <SiteHeader
        t={t}
        locale={locale}
        onLocaleChange={setLocale}
        transparentOnTop={transparentHeader}
      />
      <main>{children}</main>
      <SiteFooter t={t} />
    </div>
  );
}

export function SiteLayout({ children, transparentHeader }: SiteLayoutProps) {
  return (
    <LocaleProvider>
      <SiteShell transparentHeader={transparentHeader}>{children}</SiteShell>
    </LocaleProvider>
  );
}

export default SiteLayout;