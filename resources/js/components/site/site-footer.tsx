import { Heart, Linkedin, Facebook, Instagram, Twitter } from "lucide-react";
import type { SiteTranslations } from "../../lib/translations";

export interface SiteFooterProps {
  t: SiteTranslations;
  onSubscribe?: (email: string) => void;
}

export function SiteFooter({ t, onSubscribe }: SiteFooterProps) {
  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!onSubscribe) return;
    const form = e.currentTarget;
    const data = new FormData(form);
    const email = String(data.get("email") ?? "");
    onSubscribe(email);
    form.reset();
  };

  return (
    <footer className="bg-footer-bg text-white/80">
      <div className="mx-auto grid max-w-[1200px] gap-12 px-6 py-16 md:grid-cols-3">
        <div>
          <div className="flex items-center gap-2 font-display text-lg font-extrabold text-white">
            <span className="grid h-9 w-9 place-items-center rounded-full bg-action-red text-white">
              <Heart className="h-4 w-4" fill="currentColor" />
            </span>
            {t.brandName}
          </div>
          <p className="mt-4 max-w-sm text-sm leading-relaxed">{t.footer.blurb}</p>
          <div className="mt-6 flex items-center gap-3">
            {[
              { Icon: Linkedin, label: "LinkedIn", href: "#linkedin" },
              { Icon: Facebook, label: "Facebook", href: "#facebook" },
              { Icon: Instagram, label: "Instagram", href: "#instagram" },
              { Icon: Twitter, label: "X", href: "#x" },
            ].map(({ Icon, label, href }) => (
              <a
                key={label}
                href={href}
                aria-label={label}
                className="grid h-10 w-10 place-items-center rounded-full bg-white/5 text-white/80 transition-colors hover:bg-action-red hover:text-white"
              >
                <Icon className="h-4 w-4" />
              </a>
            ))}
          </div>
        </div>

        <div>
          <h3 className="font-display text-sm font-semibold uppercase tracking-[0.2em] text-white">
            {t.footer.pagesTitle}
          </h3>
          <ul className="mt-5 space-y-3 text-sm">
            {t.footer.pages.map((p) => (
              <li key={p.label}>
                <a href={p.href} className="hover:text-white transition-colors">
                  {p.label}
                </a>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="font-display text-sm font-semibold uppercase tracking-[0.2em] text-white">
            {t.footer.newsletterTitle}
          </h3>
          <p className="mt-5 text-sm">{t.footer.newsletterSubtitle}</p>
          <form
            onSubmit={handleSubmit}
            className="mt-4 flex flex-col gap-2 sm:flex-row"
          >
            <label className="sr-only" htmlFor="newsletter-email">
              {t.footer.emailPlaceholder}
            </label>
            <input
              id="newsletter-email"
              name="email"
              type="email"
              required
              placeholder={t.footer.emailPlaceholder}
              className="flex-1 rounded-full bg-white/5 px-4 py-3 text-sm text-white placeholder-white/40 outline-none ring-1 ring-white/10 focus:ring-action-red"
            />
            <button
              type="submit"
              className="rounded-full bg-action-red px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-red-dark"
            >
              {t.footer.subscribe}
            </button>
          </form>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-[1200px] flex-col items-center justify-between gap-3 px-6 py-6 text-xs text-white/60 md:flex-row">
          <p>{t.footer.copyright}</p>
          <ul className="flex flex-wrap items-center gap-5">
            {t.footer.bottomLinks.map((l) => (
              <li key={l.label}>
                <a href={l.href} className="hover:text-white transition-colors">
                  {l.label}
                </a>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </footer>
  );
}

export default SiteFooter;