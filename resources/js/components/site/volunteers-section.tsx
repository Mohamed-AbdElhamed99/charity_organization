import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { SiteButton } from "./site-button";
import { VolunteerCard, type Volunteer } from "./volunteer-card";

export interface VolunteersSectionProps {
  t: SiteTranslations;
  volunteers?: Volunteer[];
}

const DEFAULT_VOLUNTEERS: Volunteer[] = [
  {
    id: 1,
    name: "Mona Hassan",
    role: "Field coordinator",
    avatar:
      "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80",
  },
  {
    id: 2,
    name: "أحمد إبراهيم",
    role: "Healthcare lead",
    avatar:
      "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80",
  },
  {
    id: 3,
    name: "Yara Mostafa",
    role: "Education programs",
    avatar:
      "https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=400&q=80",
  },
  {
    id: 4,
    name: "Omar Saleh",
    role: "Logistics & supply",
    avatar:
      "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80",
  },
];

export function VolunteersSection({
  t,
  volunteers = DEFAULT_VOLUNTEERS,
}: VolunteersSectionProps) {
  return (
    <section id="volunteers" className="bg-surface-soft py-20 md:py-28">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading
            eyebrow={t.volunteers.eyebrow}
            title={t.volunteers.title}
            intro={t.volunteers.intro}
          />
          <SiteButton href="#all-volunteers" variant="ghost">
            {t.volunteers.seeMore}
          </SiteButton>
        </div>
        <div className="mt-12 grid grid-cols-2 gap-6 md:grid-cols-4">
          {volunteers.map((v) => (
            <VolunteerCard key={v.id} volunteer={v} />
          ))}
        </div>
      </div>
    </section>
  );
}

export default VolunteersSection;