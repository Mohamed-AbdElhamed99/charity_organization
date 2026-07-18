import { useEffect, type ReactNode } from "react";
import { usePage } from "@inertiajs/react";
import { SiteHeader } from "@/components/site/site-header";
import { SiteFooter } from "@/components/site/site-footer";
import { EmailVerificationBanner } from "@/components/site/email-verification-banner";
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
  const authUser = (usePage().props as { auth?: { user?: { email_verified_at: string | null } | null } })
    .auth?.user;
  const showVerificationBanner = Boolean(authUser && !authUser.email_verified_at);

  return (
    <div lang={locale} dir={dir} className={`bg-surface text-body-text ${fontClass}`}>
      {showVerificationBanner ? <EmailVerificationBanner /> : null}
      <SiteHeader
        t={t}
        locale={locale}
        onLocaleChange={setLocale}
        transparentOnTop={transparentHeader}
        topOffset={showVerificationBanner ? 40 : 0}
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