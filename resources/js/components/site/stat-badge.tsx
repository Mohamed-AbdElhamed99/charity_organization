export interface StatBadgeProps {
  number: string;
  caption: string;
  className?: string;
}

export function StatBadge({ number, caption, className = "" }: StatBadgeProps) {
  return (
    <div
      className={`rounded-2xl bg-gradient-to-br from-brand-red-dark to-brand-red text-white px-6 py-5 shadow-xl shadow-brand-red/30 max-w-xs ${className}`}
    >
      <div className="font-display text-4xl font-extrabold leading-none">
        {number}
      </div>
      <p className="mt-2 text-sm text-white/85 leading-snug">{caption}</p>
    </div>
  );
}

export default StatBadge;