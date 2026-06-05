import type { ReactNode } from "react";

export interface ActivityItem {
  id: string | number;
  title: string;
  description: string;
  image: string;
  icon?: ReactNode;
}

export interface ActivityCardProps {
  item: ActivityItem;
}

export function ActivityCard({ item }: ActivityCardProps) {
  return (
    <article className="group overflow-hidden rounded-2xl bg-surface shadow-sm ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
      <div className="relative aspect-[4/3] overflow-hidden">
        <img
          src={item.image}
          alt=""
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
          loading="lazy"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink/60 via-transparent to-transparent" />
      </div>
      <div className="p-6">
        <h3 className="font-display text-lg font-bold text-ink">{item.title}</h3>
        <p className="mt-2 text-sm text-body-text leading-relaxed">
          {item.description}
        </p>
      </div>
    </article>
  );
}

export default ActivityCard;