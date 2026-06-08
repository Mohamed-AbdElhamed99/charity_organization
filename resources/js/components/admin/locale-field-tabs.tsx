import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

type Locale = 'ar' | 'en'

type LocaleFieldTabsProps = {
  activeLocale: Locale
  onLocaleChange: (locale: Locale) => void
  className?: string
}

export function LocaleFieldTabs({
  activeLocale,
  onLocaleChange,
  className,
}: LocaleFieldTabsProps) {
  return (
    <div
      className={cn('inline-flex rounded-md border p-1', className)}
      role="tablist"
      aria-label="Content language"
    >
      <Button
        type="button"
        size="sm"
        variant={activeLocale === 'ar' ? 'default' : 'ghost'}
        role="tab"
        aria-selected={activeLocale === 'ar'}
        onClick={() => onLocaleChange('ar')}
      >
        AR
      </Button>
      <Button
        type="button"
        size="sm"
        variant={activeLocale === 'en' ? 'default' : 'ghost'}
        role="tab"
        aria-selected={activeLocale === 'en'}
        onClick={() => onLocaleChange('en')}
      >
        EN
      </Button>
    </div>
  )
}
