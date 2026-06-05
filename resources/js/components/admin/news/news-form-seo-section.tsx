import InputError from '@/components/input-error'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

type NewsFormSeoSectionProps = {
  metaTitleAr: string
  metaTitleEn: string
  metaDescriptionAr: string
  metaDescriptionEn: string
  errors: Record<string, string | undefined>
  onMetaTitleArChange: (value: string) => void
  onMetaTitleEnChange: (value: string) => void
  onMetaDescriptionArChange: (value: string) => void
  onMetaDescriptionEnChange: (value: string) => void
}

export function NewsFormSeoSection({
  metaTitleAr,
  metaTitleEn,
  metaDescriptionAr,
  metaDescriptionEn,
  errors,
  onMetaTitleArChange,
  onMetaTitleEnChange,
  onMetaDescriptionArChange,
  onMetaDescriptionEnChange,
}: NewsFormSeoSectionProps) {
  return (
    <fieldset className="space-y-4 rounded-lg border p-4">
      <legend className="px-1 text-sm font-medium">SEO</legend>

      <div className="grid gap-2">
        <Label htmlFor="meta_title_ar">Meta title (Arabic)</Label>
        <Input
          id="meta_title_ar"
          value={metaTitleAr}
          onChange={(event) => onMetaTitleArChange(event.target.value)}
          dir="rtl"
        />
        <InputError message={errors.meta_title_ar} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="meta_title_en">Meta title (English)</Label>
        <Input
          id="meta_title_en"
          value={metaTitleEn}
          onChange={(event) => onMetaTitleEnChange(event.target.value)}
        />
        <InputError message={errors.meta_title_en} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="meta_description_ar">Meta description (Arabic)</Label>
        <Textarea
          id="meta_description_ar"
          value={metaDescriptionAr}
          onChange={(event) => onMetaDescriptionArChange(event.target.value)}
          dir="rtl"
        />
        <InputError message={errors.meta_description_ar} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="meta_description_en">Meta description (English)</Label>
        <Textarea
          id="meta_description_en"
          value={metaDescriptionEn}
          onChange={(event) => onMetaDescriptionEnChange(event.target.value)}
        />
        <InputError message={errors.meta_description_en} />
      </div>
    </fieldset>
  )
}
