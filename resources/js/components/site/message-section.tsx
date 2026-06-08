import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { SiteButton } from "./site-button";

export interface MessageSectionProps {
  t: SiteTranslations;
  image?: string;
}

const DEFAULT_IMAGE =
  "https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=1200&q=80";

export function MessageSection({ t, image = DEFAULT_IMAGE }: MessageSectionProps) {
  return (
    <section className="bg-surface-soft py-20 md:py-28">
      <div className="mx-auto grid max-w-[1200px] grid-cols-1 items-center gap-12 px-6 lg:grid-cols-2">
        <div className="order-2 lg:order-1 flex flex-col gap-6">
          <SectionHeading eyebrow={t.message.eyebrow} title={t.message.title} />
          <p className="text-body-text text-base md:text-lg leading-relaxed">
            {t.message.body}
          </p>
          {/* <div>
            <SiteButton href="#about" variant="primary">
              {t.message.readMore}
            </SiteButton>
          </div> */}
        </div>
        <div className="order-1 lg:order-2">
          <div className="overflow-hidden rounded-2xl shadow-xl">
            <img
              src={image}
              alt=""
              className="aspect-[6/5] w-full object-cover"
              loading="lazy"
            />
          </div>
        </div>
      </div>
    </section>
  );
}

export default MessageSection;