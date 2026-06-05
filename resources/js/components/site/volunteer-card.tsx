export interface Volunteer {
  id: string | number;
  name: string;
  role: string;
  avatar: string;
}

export interface VolunteerCardProps {
  volunteer: Volunteer;
}

export function VolunteerCard({ volunteer }: VolunteerCardProps) {
  return (
    <div className="group flex flex-col items-center rounded-2xl bg-surface p-6 text-center shadow-sm ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl">
      <div className="relative h-28 w-28 overflow-hidden rounded-full ring-4 ring-brand-red/10">
        <img
          src={volunteer.avatar}
          alt={volunteer.name}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
          loading="lazy"
        />
      </div>
      <h3
        dir="auto"
        className="mt-4 font-display text-lg font-bold text-ink"
      >
        {volunteer.name}
      </h3>
      <p dir="auto" className="text-sm text-body-text">
        {volunteer.role}
      </p>
    </div>
  );
}

export default VolunteerCard;