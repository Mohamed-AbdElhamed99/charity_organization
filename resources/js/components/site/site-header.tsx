import { useEffect, useState } from "react";
import { Menu, X, ChevronDown } from "lucide-react";
import type { Locale, SiteTranslations } from "../../lib/translations";
import { SiteButton } from "./site-button";
import { LangSwitch } from "./lang-switch";
import { route } from "ziggy-js";

export interface SiteHeaderProps {
  t: SiteTranslations;
  locale: Locale;
  onLocaleChange?: (locale: Locale) => void;
  /** When true, header starts transparent over the hero. */
  transparentOnTop?: boolean;
}

export function SiteHeader({
  t,
  locale,
  onLocaleChange,
  transparentOnTop = true,
}: SiteHeaderProps) {
  const [scrolled, setScrolled] = useState(!transparentOnTop);
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    if (!transparentOnTop) return;
    const onScroll = () => setScrolled(window.scrollY > 40);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, [transparentOnTop]);

  const navLinks = [
    { label: t.nav.home, href: "/" },
    { label: t.nav.news, href: "/news" },
    { label: t.nav.campaigns, href: "/campaigns" },
    { label: t.nav.donations, href: "#donations" },
    { label: t.nav.contact, href: "/contact" },
  ];

  const headerBg = scrolled
    ? "bg-white/95 backdrop-blur shadow-sm"
    : "bg-transparent";
  const linkColor = scrolled ? "text-ink" : "text-white";

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${headerBg}`}
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
          <SiteButton href="#donate" variant="primary">
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
            <div className="mt-3 flex items-center justify-between gap-3 border-t border-black/5 pt-4">
              <LangSwitch
                t={t}
                locale={locale}
                onLocaleChange={onLocaleChange}
                tone="dark"
              />
              <SiteButton href="#donate" variant="primary">
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