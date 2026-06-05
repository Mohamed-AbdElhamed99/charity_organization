export interface SectionHeadingProps {
  eyebrow?: string;
  title: string;
  align?: "left" | "center";
  intro?: string;
}

export function SectionHeading({
  eyebrow,
  title,
  align = "left",
  intro,
}: SectionHeadingProps) {
  const alignment =
    align === "center" ? "text-center mx-auto items-center" : "text-start items-start";
  return (
    <div className={`flex flex-col gap-4 max-w-2xl ${alignment}`}>
      {eyebrow ? (
        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-gold">
          {eyebrow}
        </span>
      ) : null}
      <h2 className="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-ink leading-tight">
        {title}
      </h2>
      {intro ? (
        <p className="text-body-text text-base md:text-lg leading-relaxed">{intro}</p>
      ) : null}
    </div>
  );
}

export default SectionHeading;