import { ArrowRight } from "lucide-react";
import type { SiteTranslations } from "../../lib/translations";
import { SiteButton } from "./site-button";

export interface SiteHeroProps {
  t: SiteTranslations;
  backgroundImage?: string;
}

const DEFAULT_BG =
  "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1920&q=80";

export function SiteHero({ t, backgroundImage = DEFAULT_BG }: SiteHeroProps) {
  return (
    <section
      id="home"
      className="relative isolate min-h-[640px] md:min-h-[760px] flex items-center overflow-hidden"
    >
      <div className="absolute inset-0 -z-10">
        <img
          src={backgroundImage}
          alt=""
          className="h-full w-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-br from-brand-red-dark/85 via-ink/70 to-ink/40" />
      </div>

      <div className="mx-auto w-full max-w-[1200px] px-6 pt-32 pb-20 md:pt-40 md:pb-28">
        <div className="max-w-2xl text-white">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-gold backdrop-blur">
            {t.hero.eyebrow}
          </span>
          <h1 className="mt-6 font-display text-4xl md:text-5xl lg:text-6xl font-extrabold leading-[1.1]">
            {t.hero.title}
          </h1>
          <p className="mt-6 text-lg md:text-xl text-white/85 leading-relaxed max-w-xl">
            {t.hero.subtitle}
          </p>
          <div className="mt-10 flex flex-wrap items-center gap-4">
            <SiteButton
              href="#donate"
              variant="primary"
              icon={<ArrowRight className="h-4 w-4" />}
            >
              {t.hero.donateCta}
            </SiteButton>
            {/* <SiteButton href="#mission" variant="outline">
              {t.hero.learnMore}
            </SiteButton> */}
          </div>
        </div>
      </div>
    </section>
  );
}

export default SiteHero;