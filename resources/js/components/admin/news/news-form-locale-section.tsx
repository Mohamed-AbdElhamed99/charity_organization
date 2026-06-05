import InputError from '@/components/input-error'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

type NewsFormLocaleSectionProps = {
  locale: 'ar' | 'en'
  title: string
  subtitle: string
  excerpt: string
  body: string
  errors: Record<string, string | undefined>
  onTitleChange: (value: string) => void
  onSubtitleChange: (value: string) => void
  onExcerptChange: (value: string) => void
  onBodyChange: (value: string) => void
}

const localeLabels = {
  ar: {
    heading: 'Arabic Content',
    title: 'Title (Arabic)',
    subtitle: 'Subtitle (Arabic)',
    excerpt: 'Excerpt (Arabic)',
    body: 'Body (Arabic)',
  },
  en: {
    heading: 'English Content',
    title: 'Title (English)',
    subtitle: 'Subtitle (English)',
    excerpt: 'Excerpt (English)',
    body: 'Body (English)',
  },
} as const

export function NewsFormLocaleSection({
  locale,
  title,
  subtitle,
  excerpt,
  body,
  errors,
  onTitleChange,
  onSubtitleChange,
  onExcerptChange,
  onBodyChange,
}: NewsFormLocaleSectionProps) {
  const labels = localeLabels[locale]
  const prefix = locale === 'ar' ? 'ar' : 'en'

  return (
    <fieldset className="space-y-4 rounded-lg border p-4">
      <legend className="px-1 text-sm font-medium">{labels.heading}</legend>

      <div className="grid gap-2">
        <Label htmlFor={`title_${prefix}`}>{labels.title}</Label>
        <Input
          id={`title_${prefix}`}
          value={title}
          onChange={(event) => onTitleChange(event.target.value)}
          required={locale === 'en' || locale === 'ar'}
          dir={locale === 'ar' ? 'rtl' : 'ltr'}
        />
        <InputError message={errors[`title_${prefix}`]} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor={`subtitle_${prefix}`}>{labels.subtitle}</Label>
        <Input
          id={`subtitle_${prefix}`}
          value={subtitle}
          onChange={(event) => onSubtitleChange(event.target.value)}
          dir={locale === 'ar' ? 'rtl' : 'ltr'}
        />
        <InputError message={errors[`subtitle_${prefix}`]} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor={`excerpt_${prefix}`}>{labels.excerpt}</Label>
        <Textarea
          id={`excerpt_${prefix}`}
          value={excerpt}
          onChange={(event) => onExcerptChange(event.target.value)}
          dir={locale === 'ar' ? 'rtl' : 'ltr'}
        />
        <InputError message={errors[`excerpt_${prefix}`]} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor={`body_${prefix}`}>{labels.body}</Label>
        <Textarea
          id={`body_${prefix}`}
          value={body}
          onChange={(event) => onBodyChange(event.target.value)}
          className="min-h-32"
          dir={locale === 'ar' ? 'rtl' : 'ltr'}
        />
        <InputError message={errors[`body_${prefix}`]} />
      </div>
    </fieldset>
  )
}
