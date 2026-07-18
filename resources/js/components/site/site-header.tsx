import { useEffect, useState } from "react";
import { Menu, X, ChevronDown, User as UserIcon } from "lucide-react";
import { Link, router, usePage } from "@inertiajs/react";
import type { Locale, SiteTranslations } from "../../lib/translations";
import { SiteButton } from "./site-button";
import { LangSwitch } from "./lang-switch";
import { route } from "ziggy-js";

type AuthUser = {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
};

export interface SiteHeaderProps {
  t: SiteTranslations;
  locale: Locale;
  onLocaleChange?: (locale: Locale) => void;
  /** When true, header starts transparent over the hero. */
  transparentOnTop?: boolean;
  /** Pixels to push the fixed header down by (e.g. for a banner above it). */
  topOffset?: number;
}

export function SiteHeader({
  t,
  locale,
  onLocaleChange,
  transparentOnTop = true,
  topOffset = 0,
}: SiteHeaderProps) {
  const [scrolled, setScrolled] = useState(!transparentOnTop);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [accountMenuOpen, setAccountMenuOpen] = useState(false);
  const authUser = (usePage().props as { auth?: { user?: AuthUser | null } }).auth?.user ?? null;

  const logout = () => {
    router.post(route("account.logout"));
  };

  useEffect(() => {
    if (!transparentOnTop) return;
    const onScroll = () => setScrolled(window.scrollY > 40);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [transparentOnTop]);

  const navLinks = [
    { label: t.nav.home, href: route("home") },
    { label: t.nav.news, href: route("news.index") },
    { label: t.nav.campaigns, href: route("campaigns.index") },
    { label: t.nav.donations, href: route("donations.index") },
    { label: t.nav.contact, href: route("contact") },
  ];

  const headerBg = scrolled
    ? "bg-white/95 backdrop-blur shadow-sm"
    : "bg-transparent";
  const linkColor = scrolled ? "text-ink" : "text-white";

  return (
    <header
      style={{ top: topOffset }}
      className={`fixed inset-x-0 z-50 transition-all duration-300 ${headerBg}`}
    >
      <div className="mx-auto flex max-w-[1200px] items-center justify-between gap-6 px-6 py-4">
        <a
          href={route('home')}
          className={`flex items-center gap-2 font-display text-lg font-extrabold tracking-tight ${linkColor}`}
        >
          <img
            src="/images/new-egypt-logo.png"
            alt={t.brandName}
            className="h-9 w-9 object-contain"
          />
          <span>{t.brandName}</span>
        </a>

        <nav className="hidden lg:flex items-center gap-1">
          {navLinks.map((link) => (
            <a
              key={link.label}
              href={link.href}
              className={`inline-flex items-center gap-1 rounded-full px-4 py-2 text-sm font-medium transition-colors ${linkColor} hover:bg-white/10 ${scrolled ? "hover:bg-ink/5" : ""}`}
            >
              {link.label}
              {/* {link.hasMenu ? <ChevronDown className="h-3.5 w-3.5" /> : null} */}
            </a>
          ))}
        </nav>

        <div className="hidden lg:flex items-center gap-3">
          <LangSwitch
            t={t}
            locale={locale}
            onLocaleChange={onLocaleChange}
            tone={scrolled ? "dark" : "light"}
          />
          {authUser ? (
            <div className="relative">
              <button
                type="button"
                onClick={() => setAccountMenuOpen((v) => !v)}
                className={`inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium ${linkColor} hover:bg-white/10 ${scrolled ? "hover:bg-ink/5" : ""}`}
              >
                <UserIcon className="h-4 w-4" />
                <span className="max-w-[120px] truncate">{authUser.name}</span>
                <ChevronDown className="h-3.5 w-3.5" />
              </button>

              {accountMenuOpen ? (
                <div className="absolute right-0 mt-2 w-48 rounded-xl bg-white py-2 shadow-xl ring-1 ring-black/5 rtl:left-0 rtl:right-auto">
                  <Link
                    href={route("account.profile.edit")}
                    className="block px-4 py-2 text-sm text-ink hover:bg-ink/5"
                    onClick={() => setAccountMenuOpen(false)}
                  >
                    {t.accountNav.profile}
                  </Link>
                  <Link
                    href={route("account.donations.index")}
                    className="block px-4 py-2 text-sm text-ink hover:bg-ink/5"
                    onClick={() => setAccountMenuOpen(false)}
                  >
                    {t.accountNav.myDonations}
                  </Link>
                  <Link
                    href={route("account.payment-methods.index")}
                    className="block px-4 py-2 text-sm text-ink hover:bg-ink/5"
                    onClick={() => setAccountMenuOpen(false)}
                  >
                    {t.accountPage.paymentMethods.title}
                  </Link>
                  <button
                    type="button"
                    onClick={logout}
                    className="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 rtl:text-right"
                  >
                    {t.accountNav.logout}
                  </button>
                </div>
              ) : null}
            </div>
          ) : (
            <a
              href={route("account.login")}
              className={`text-sm font-medium ${linkColor} hover:opacity-80`}
            >
              {t.accountNav.login}
            </a>
          )}
          <SiteButton href={route("donate.general")} variant="primary">
            {t.nav.donateCta}
          </SiteButton>
        </div>

        <button
          type="button"
          className={`lg:hidden inline-flex items-center justify-center rounded-md p-2 ${linkColor}`}
          aria-label={t.nav.menu}
          aria-expanded={mobileOpen}
          onClick={() => setMobileOpen((v) => !v)}
        >
          {mobileOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {mobileOpen ? (
        <div className="lg:hidden bg-white shadow-lg border-t border-black/5">
          <nav className="mx-auto flex max-w-[1200px] flex-col px-6 py-4">
            {navLinks.map((link) => (
              <a
                key={link.label}
                href={link.href}
                className="rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-ink/5"
                onClick={() => setMobileOpen(false)}
              >
                {link.label}
              </a>
            ))}
            {authUser ? (
              <>
                <a href={route("account.profile.edit")} className="rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-ink/5">
                  {t.accountNav.profile}
                </a>
                <a href={route("account.donations.index")} className="rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-ink/5">
                  {t.accountNav.myDonations}
                </a>
                <a href={route("account.payment-methods.index")} className="rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-ink/5">
                  {t.accountPage.paymentMethods.title}
                </a>
                <button type="button" onClick={logout} className="rounded-lg px-3 py-3 text-left text-base font-medium text-red-600 hover:bg-red-50">
                  {t.accountNav.logout}
                </button>
              </>
            ) : (
              <a href={route("account.login")} className="rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-ink/5">
                {t.accountNav.login}
              </a>
            )}
            <div className="mt-3 flex items-center justify-between gap-3 border-t border-black/5 pt-4">
              <LangSwitch
                t={t}
                locale={locale}
                onLocaleChange={onLocaleChange}
                tone="dark"
              />
              <SiteButton href={route("donate.general")} variant="primary">
                {t.nav.donateCta}
              </SiteButton>
            </div>
          </nav>
        </div>
      ) : null}
    </header>
  );
}

interface LangSwitchProps {
  t: SiteTranslations;
  locale: Locale;
  onLocaleChange?: (locale: Locale) => void;
  tone: "dark" | "light";
}


export default SiteHeader;