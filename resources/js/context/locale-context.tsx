import { route } from 'ziggy-js';
import { createContext, useContext } from "react";
import { router, usePage } from "@inertiajs/react";
import { dirFor, translations, type Locale } from "@/lib/translations";

type LocaleContextValue = {
    locale: Locale;
    dir: "ltr" | "rtl";
    setLocale: (l: Locale) => void;
    t: (typeof translations)[Locale];
};

const LocaleContext = createContext<LocaleContextValue | null>(null);

export function useLocale() {
    const ctx = useContext(LocaleContext);
    if (!ctx) throw new Error("useLocale must be used within SiteLayout");
    return ctx;
}

export function LocaleProvider({ children }: { children: React.ReactNode }) {
  const page = usePage().props as { locale?: Locale; dir?: "ltr" | "rtl" };
  const locale: Locale = page.locale ?? "en";
  const dir = page.dir ?? dirFor(locale);   // import dirFor from translations

  const setLocale = (l: Locale) => {
    router.visit(route("lang.switch", { locale: l }), { preserveScroll: true });
  };

  return (
    <LocaleContext.Provider value={{ locale, dir, setLocale, t: translations[locale] }}>
      {children}
    </LocaleContext.Provider>
  );
}