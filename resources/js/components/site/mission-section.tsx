import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { SiteButton } from "./site-button";
import { StatBadge } from "./stat-badge";

export interface MissionSectionProps {
  t: SiteTranslations;
  image?: string;
}

const DEFAULT_IMAGE =
  "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1200&q=80";

export function MissionSection({ t, image = DEFAULT_IMAGE }: MissionSectionProps) {
  return (
    <section id="mission" className="bg-surface py-20 md:py-28">
      <div className="mx-auto grid max-w-[1200px] grid-cols-1 items-center gap-12 px-6 lg:grid-cols-2">
        <div className="relative">
          <div className="overflow-hidden rounded-2xl shadow-xl">
            <img
              src={image}
              alt=""
              className="aspect-[5/6] w-full object-cover"
              loading="lazy"
            />
          </div>
          <StatBadge
            number={t.mission.statNumber}
            caption={t.mission.statCaption}
            className="absolute -bottom-6 end-[-1rem] md:end-[-2rem]"
          />
        </div>
        <div className="flex flex-col gap-6">
          <SectionHeading eyebrow={t.mission.eyebrow} title={t.mission.title} />
          <p className="text-body-text text-base md:text-lg leading-relaxed">
            {t.mission.body}
          </p>
          {/* <div>
            <SiteButton href="#about" variant="primary">
              {t.mission.readMore}
            </SiteButton>
          </div> */}
        </div>
      </div>
    </section>
  );
}

export default MissionSection;