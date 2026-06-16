import type { ReactNode, MouseEventHandler } from "react";

export type SiteButtonVariant = "primary" | "outline" | "ghost";

export interface SiteButtonProps {
  variant?: SiteButtonVariant;
  href?: string;
  icon?: ReactNode;
  children: ReactNode;
  type?: "button" | "submit";
  onClick?: MouseEventHandler<HTMLButtonElement | HTMLAnchorElement>;
  className?: string;
  disabled?: boolean;
  ariaLabel?: string;
}

const base =
  "inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold tracking-wide transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-action-red disabled:opacity-50";

const variants: Record<SiteButtonVariant, string> = {
  primary:
    "bg-action-red text-white shadow-lg shadow-action-red/30 hover:bg-brand-red-dark hover:-translate-y-0.5 hover:shadow-xl",
  outline:
    "border-2 border-white/80 text-white hover:bg-white hover:text-ink",
  ghost: "text-ink hover:bg-ink/5",
};

export function SiteButton({
  variant = "primary",
  href,
  icon,
  children,
  type = "button",
  onClick,
  className = "",
  ariaLabel,
  disabled = false,
}: SiteButtonProps) {
  const cls = `${base} ${variants[variant]} ${className}`;
  const content = (
    <>
      <span>{children}</span>
      {icon ? (
        <span className="inline-flex rtl:-scale-x-100 transition-transform group-hover:translate-x-0.5">
          {icon}
        </span>
      ) : null}
    </>
  );
  if (href) {
    return (
      <a
        href={href}
        className={`group ${cls}`}
        aria-label={ariaLabel}
        onClick={onClick as MouseEventHandler<HTMLAnchorElement>}
      >
        {content}
      </a>
    );
  }
  return (
    <button
      type={type}
      className={`group ${cls}`}
      aria-label={ariaLabel}
      disabled={disabled}
      onClick={onClick as MouseEventHandler<HTMLButtonElement>}
    >
      {content}
    </button>
  );
}

export default SiteButton;