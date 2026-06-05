import InputError from '@/components/input-error'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { NewsCategory } from '@/types/models/news'

type NewsFormSettingsSectionProps = {
  slug: string
  categoryId: string
  videoUrl: string
  publishedAt: string
  isActive: boolean
  isPrivate: boolean
  categories: NewsCategory[]
  errors: Record<string, string | undefined>
  isEdit: boolean
  onSlugChange: (value: string) => void
  onCategoryChange: (value: string) => void
  onVideoUrlChange: (value: string) => void
  onPublishedAtChange: (value: string) => void
  onIsActiveChange: (value: boolean) => void
  onIsPrivateChange: (value: boolean) => void
}

export function NewsFormSettingsSection({
  slug,
  categoryId,
  videoUrl,
  publishedAt,
  isActive,
  isPrivate,
  categories,
  errors,
  isEdit,
  onSlugChange,
  onCategoryChange,
  onVideoUrlChange,
  onPublishedAtChange,
  onIsActiveChange,
  onIsPrivateChange,
}: NewsFormSettingsSectionProps) {
  return (
    <fieldset className="space-y-4 rounded-lg border p-4">
      <legend className="px-1 text-sm font-medium">Settings</legend>

      <div className="grid gap-2">
        <Label htmlFor="slug">Slug {isEdit ? '' : '(optional)'}</Label>
        <Input
          id="slug"
          value={slug}
          onChange={(event) => onSlugChange(event.target.value)}
          placeholder="Auto-generated from English title if empty"
        />
        <InputError message={errors.slug} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="category_id">Category</Label>
        <Select value={categoryId || undefined} onValueChange={onCategoryChange}>
          <SelectTrigger id="category_id" className="w-full">
            <SelectValue placeholder="Select a category" />
          </SelectTrigger>
          <SelectContent>
            {categories.map((category) => (
              <SelectItem key={category.id} value={String(category.id)}>
                {category.name_en}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <InputError message={errors.category_id} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="video_url">Video embed URL</Label>
        <Input
          id="video_url"
          type="url"
          value={videoUrl}
          onChange={(event) => onVideoUrlChange(event.target.value)}
          placeholder="https://youtube.com/..."
        />
        <InputError message={errors.video_url} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="published_at">Published date</Label>
        <Input
          id="published_at"
          type="date"
          value={publishedAt}
          onChange={(event) => onPublishedAtChange(event.target.value)}
        />
        <InputError message={errors.published_at} />
      </div>

      <div className="flex items-center gap-2">
        <Checkbox
          id="is_active"
          checked={isActive}
          onCheckedChange={(checked) => onIsActiveChange(checked === true)}
        />
        <Label htmlFor="is_active">Active</Label>
      </div>
      <InputError message={errors.is_active} />

      <div className="flex items-center gap-2">
        <Checkbox
          id="is_private"
          checked={isPrivate}
          onCheckedChange={(checked) => onIsPrivateChange(checked === true)}
        />
        <Label htmlFor="is_private">Members only (private)</Label>
      </div>
      <InputError message={errors.is_private} />
    </fieldset>
  )
}
