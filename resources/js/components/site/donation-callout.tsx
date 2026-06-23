import { Heart, ArrowRight } from "lucide-react";
import type { SiteTranslations } from "../../lib/translations";
import { SiteButton } from "./site-button";
import { route } from "ziggy-js";

export interface DonationCalloutProps {
  t: SiteTranslations;
}

export function DonationCallout({ t }: DonationCalloutProps) {
  return (
    <section id="donate" className="bg-surface py-20 md:py-24">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-red-dark via-brand-red to-action-red px-8 py-14 md:px-16 md:py-20 text-white shadow-2xl shadow-brand-red/30">
          {/* decorative blobs */}
          <div className="pointer-events-none absolute -end-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
          <div className="pointer-events-none absolute -start-20 -bottom-20 h-72 w-72 rounded-full bg-gold/20 blur-3xl" />
          <div className="relative grid items-center gap-8 md:grid-cols-[1fr_auto]">
            <div>
              <div className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                <Heart className="h-7 w-7" fill="currentColor" />
              </div>
              <span className="mt-6 block text-xs font-semibold uppercase tracking-[0.2em] text-gold">
                {t.donationCallout.eyebrow}
              </span>
              <h2 className="mt-2 font-display text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight">
                {t.donationCallout.title}
              </h2>
              <p className="mt-4 max-w-xl text-base md:text-lg text-white/85">
                {t.donationCallout.subtitle}
              </p>
            </div>
            <div className="md:justify-self-end">
              <SiteButton
                href={route('donate.general')}
                variant="primary"
                icon={<ArrowRight className="h-4 w-4" />}
                className="bg-white !text-action-red hover:!bg-gold hover:!text-ink shadow-xl"
              >
                {t.donationCallout.cta}
              </SiteButton>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default DonationCallout;