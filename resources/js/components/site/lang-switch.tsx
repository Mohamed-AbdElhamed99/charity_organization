import type { Locale, SiteTranslations } from "@/lib/translations";

interface LangSwitchProps {
  t: SiteTranslations;
  locale: Locale;
  onLocaleChange?: (locale: Locale) => void;
  tone: "dark" | "light";
}

export function LangSwitch({ t, locale, onLocaleChange, tone }: LangSwitchProps) {
  const baseTone =
    tone === "dark" ? "border-ink/15 text-ink" : "border-white/40 text-white";
  const activeTone = tone === "dark" ? "bg-ink text-white" : "bg-white text-ink";

  // With onLocaleChange (from useLocale) this renders buttons that trigger the
  // Inertia visit to lang.switch. Without it, falls back to ?lang= anchors.
  const renderItem = (target: Locale, label: string) => {
    const isActive = locale === target;
    const cls = `px-3 py-1 text-xs font-semibold transition-colors ${isActive ? activeTone : "hover:bg-black/5"}`;
    if (onLocaleChange) {
      return (
        <button
          key={target}
          type="button"
          onClick={() => onLocaleChange(target)}
          className={cls}
          aria-pressed={isActive}
        >
          {label}
        </button>
      );
    }
    return (
      <a
        key={target}
        href={`?lang=${target}`}
        className={cls}
        aria-current={isActive ? "true" : undefined}
      >
        {label}
      </a>
    );
  };

  return (
    <div
      role="group"
      aria-label={t.langSwitch.label}
      className={`inline-flex overflow-hidden rounded-full border ${baseTone}`}
    >
      {renderItem("en", t.langSwitch.en)}
      {renderItem("ar", t.langSwitch.ar)}
    </div>
  );
}

export default LangSwitch;