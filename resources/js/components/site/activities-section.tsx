import type { SiteTranslations } from "../../lib/translations";
import { SectionHeading } from "./section-heading";
import { SiteButton } from "./site-button";
import { ActivityCard, type ActivityItem } from "./activity-card";

export interface ActivitiesSectionProps {
  t: SiteTranslations;
  activities?: ActivityItem[];
}

const DEFAULT_ACTIVITIES: ActivityItem[] = [
  {
    id: 1,
    title: "Education for all",
    description:
      "Scholarships, school supplies, and after-school tutoring for underserved students across Egypt.",
    image:
      "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80",
  },
  {
    id: 2,
    title: "Healthcare outreach",
    description:
      "Mobile clinics, free check-ups, and medication delivery for remote communities and elders.",
    image:
      "https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=800&q=80",
  },
  {
    id: 3,
    title: "Food distribution",
    description:
      "Regular food parcels and hot-meal programs that reach thousands of families every month.",
    image:
      "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=800&q=80",
  },
  {
    id: 4,
    title: "Community development",
    description:
      "Rebuilding shared spaces, training local leaders, and funding small enterprise grants.",
    image:
      "https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=800&q=80",
  },
];

export function ActivitiesSection({
  t,
  activities = DEFAULT_ACTIVITIES,
}: ActivitiesSectionProps) {
  return (
    <section id="activities" className="bg-surface py-20 md:py-28">
      <div className="mx-auto max-w-[1200px] px-6">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading
            eyebrow={t.activities.eyebrow}
            title={t.activities.title}
            intro={t.activities.intro}
          />
          <SiteButton href="#all-activities" variant="ghost">
            {t.activities.seeMore}
          </SiteButton>
        </div>
        <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {activities.map((item) => (
            <ActivityCard key={item.id} item={item} />
          ))}
        </div>
      </div>
    </section>
  );
}

export default ActivitiesSection;