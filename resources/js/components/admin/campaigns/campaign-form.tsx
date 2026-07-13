import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useForm } from '@inertiajs/react'
import { X } from 'lucide-react'
import {
  MeetingMultiSelect,
  type MeetingSelectOption,
} from '@/components/admin/campaigns/meeting-multi-select'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
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
import { Textarea } from '@/components/ui/textarea'
import { RichTextEditor } from '@/components/rich-text-editor/rich-text-editor'
import { recurrenceOptions, statusOptions } from './data/data'
import type {
  Campaign,
  CampaignCategoryOption,
  CampaignGalleryItem,
} from '@/types/models/campaign'
import type { CampaignRecurrence, CampaignStatus } from '@/types/enums'

type PendingGalleryItem = {
  file: File
  previewUrl: string
}

type CampaignFormProps = {
  campaign?: Campaign
  categories: CampaignCategoryOption[]
  meetingOptions: MeetingSelectOption[]
  submitUrl: string
  method: 'post' | 'patch'
  submitLabel: string
  cancelUrl: string
}

function buildInitialFormData(campaign?: Campaign) {
  return {
    title_ar: campaign?.title_ar ?? '',
    title_en: campaign?.title_en ?? '',
    slug: campaign?.slug ?? '',
    excerpt_ar: campaign?.excerpt_ar ?? '',
    excerpt_en: campaign?.excerpt_en ?? '',
    description_ar: campaign?.description_ar ?? '',
    description_en: campaign?.description_en ?? '',
    category_id: campaign?.category_id ? String(campaign.category_id) : '',
    status: (campaign?.status ?? 'draft') as CampaignStatus,
    budget: campaign?.budget != null ? String(campaign.budget) : '',
    donation_target:
      campaign?.donation_target != null
        ? String(campaign.donation_target)
        : '',
    start_date: campaign?.start_date ?? '',
    end_date: campaign?.end_date ?? '',
    is_public: campaign?.is_public ?? true,
    open_donation_form: campaign?.open_donation_form ?? false,
    is_repeated: (campaign?.is_repeated ?? 'never') as CampaignRecurrence,
    repeat_until: campaign?.repeat_until ?? '',
    meta_title_ar: campaign?.meta_title_ar ?? '',
    meta_title_en: campaign?.meta_title_en ?? '',
    meta_description_ar: campaign?.meta_description_ar ?? '',
    meta_description_en: campaign?.meta_description_en ?? '',
    cover: null as File | null,
    gallery: [] as File[],
    removed_gallery_ids: [] as number[],
    meeting_ids: (campaign?.meeting_ids ?? []).map(String),
  }
}

export function CampaignForm({
  campaign,
  categories,
  meetingOptions,
  submitUrl,
  method,
  submitLabel,
  cancelUrl,
}: CampaignFormProps) {
  const isEdit = !!campaign
  const galleryInputRef = useRef<HTMLInputElement>(null)
  const [existingGallery, setExistingGallery] = useState<CampaignGalleryItem[]>(
    campaign?.gallery ?? [],
  )
  const [pendingGallery, setPendingGallery] = useState<PendingGalleryItem[]>([])

  const form = useForm(buildInitialFormData(campaign))

  useEffect(() => {
    form.clearErrors()
    form.setData(buildInitialFormData(campaign))
    setExistingGallery(campaign?.gallery ?? [])
    setPendingGallery([])
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [campaign?.id])

  const coverPreview = useMemo(() => {
    if (form.data.cover) {
      return URL.createObjectURL(form.data.cover)
    }

    return campaign?.cover_url || null
  }, [form.data.cover, campaign?.cover_url])

  useEffect(() => {
    return () => {
      if (form.data.cover && coverPreview?.startsWith('blob:')) {
        URL.revokeObjectURL(coverPreview)
      }
    }
  }, [form.data.cover, coverPreview])

  const handleGalleryAdd = (files: File[]) => {
    const newItems = files.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }))
    const updated = [...pendingGallery, ...newItems]
    setPendingGallery(updated)
    form.setData(
      'gallery',
      updated.map((item) => item.file),
    )
  }

  const handleRemovePendingGalleryItem = (index: number) => {
    const item = pendingGallery[index]
    URL.revokeObjectURL(item.previewUrl)
    const updated = pendingGallery.filter((_, i) => i !== index)
    setPendingGallery(updated)
    form.setData(
      'gallery',
      updated.map((item) => item.file),
    )
  }

  const handleRemoveGalleryItem = (id: number) => {
    setExistingGallery((items) => items.filter((item) => item.id !== id))
    form.setData('removed_gallery_ids', [...form.data.removed_gallery_ids, id])
  }

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      category_id: data.category_id ? Number(data.category_id) : null,
      budget: data.budget ? Number(data.budget) : 0,
      donation_target: data.donation_target
        ? Number(data.donation_target)
        : null,
      is_public: data.is_public ? 1 : 0,
      open_donation_form: data.open_donation_form ? 1 : 0,
      repeat_until:
        data.is_repeated === 'never' ? null : data.repeat_until || null,
      meeting_ids: data.meeting_ids.map((id) => Number(id)),
    }))

    const options = {
      forceFormData: true,
      preserveScroll: true,
    }

    if (method === 'patch') {
      form.patch(submitUrl, options)
      return
    }

    form.post(submitUrl, options)
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">Arabic Content</legend>
        <div className="grid gap-2">
          <Label htmlFor="title_ar">Title (Arabic)</Label>
          <Input
            id="title_ar"
            dir="rtl"
            value={form.data.title_ar}
            onChange={(e) => form.setData('title_ar', e.target.value)}
            required
          />
          <InputError message={form.errors.title_ar} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="excerpt_ar">Excerpt (Arabic)</Label>
          <Textarea
            id="excerpt_ar"
            dir="rtl"
            value={form.data.excerpt_ar}
            onChange={(e) => form.setData('excerpt_ar', e.target.value)}
          />
          <InputError message={form.errors.excerpt_ar} />
        </div>
        <div className="grid gap-2">
          <Label>Description (Arabic)</Label>
          <RichTextEditor
            value={form.data.description_ar}
            onChange={(html) => form.setData('description_ar', html)}
            dir="rtl"
            minHeight="8rem"
          />
          <InputError message={form.errors.description_ar} />
        </div>
      </fieldset>

      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">English Content</legend>
        <div className="grid gap-2">
          <Label htmlFor="title_en">Title (English)</Label>
          <Input
            id="title_en"
            value={form.data.title_en}
            onChange={(e) => form.setData('title_en', e.target.value)}
            required
          />
          <InputError message={form.errors.title_en} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="excerpt_en">Excerpt (English)</Label>
          <Textarea
            id="excerpt_en"
            value={form.data.excerpt_en}
            onChange={(e) => form.setData('excerpt_en', e.target.value)}
          />
          <InputError message={form.errors.excerpt_en} />
        </div>
        <div className="grid gap-2">
          <Label>Description (English)</Label>
          <RichTextEditor
            value={form.data.description_en}
            onChange={(html) => form.setData('description_en', html)}
            dir="ltr"
            minHeight="8rem"
          />
          <InputError message={form.errors.description_en} />
        </div>
      </fieldset>

      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">Settings</legend>
        <div className="grid gap-2">
          <Label htmlFor="slug">Slug {isEdit ? '' : '(optional)'}</Label>
          <Input
            id="slug"
            value={form.data.slug}
            onChange={(e) => form.setData('slug', e.target.value)}
            placeholder="Auto-generated from English title if empty"
          />
          <InputError message={form.errors.slug} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="category_id">Category</Label>
          <Select
            value={form.data.category_id || undefined}
            onValueChange={(value) => form.setData('category_id', value)}
          >
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
          <InputError message={form.errors.category_id} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="status">Status</Label>
          <Select
            value={form.data.status}
            onValueChange={(value) =>
              form.setData('status', value as CampaignStatus)
            }
          >
            <SelectTrigger id="status" className="w-full">
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              {statusOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.status} />
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="grid gap-2">
            <Label htmlFor="budget">Budget</Label>
            <Input
              id="budget"
              type="number"
              min="0"
              step="0.01"
              value={form.data.budget}
              onChange={(e) => form.setData('budget', e.target.value)}
              required
            />
            <InputError message={form.errors.budget} />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="donation_target">Donation Target</Label>
            <Input
              id="donation_target"
              type="number"
              min="0"
              step="0.01"
              value={form.data.donation_target}
              onChange={(e) => form.setData('donation_target', e.target.value)}
            />
            <InputError message={form.errors.donation_target} />
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="grid gap-2">
            <Label htmlFor="start_date">Start Date</Label>
            <Input
              id="start_date"
              type="date"
              value={form.data.start_date}
              onChange={(e) => form.setData('start_date', e.target.value)}
            />
            <InputError message={form.errors.start_date} />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="end_date">End Date</Label>
            <Input
              id="end_date"
              type="date"
              value={form.data.end_date}
              onChange={(e) => form.setData('end_date', e.target.value)}
            />
            <InputError message={form.errors.end_date} />
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Checkbox
            id="is_public"
            checked={form.data.is_public}
            onCheckedChange={(checked) =>
              form.setData('is_public', checked === true)
            }
          />
          <Label htmlFor="is_public">Public</Label>
        </div>
        <InputError message={form.errors.is_public} />
        <div className="flex items-center gap-2">
          <Checkbox
            id="open_donation_form"
            checked={form.data.open_donation_form}
            onCheckedChange={(checked) =>
              form.setData('open_donation_form', checked === true)
            }
          />
          <Label htmlFor="open_donation_form">Open donation form</Label>
        </div>
        <InputError message={form.errors.open_donation_form} />
        <div className="grid gap-2">
          <Label htmlFor="is_repeated">Recurrence</Label>
          <Select
            value={form.data.is_repeated}
            onValueChange={(value) =>
              form.setData('is_repeated', value as CampaignRecurrence)
            }
          >
            <SelectTrigger id="is_repeated" className="w-full">
              <SelectValue placeholder="Select recurrence" />
            </SelectTrigger>
            <SelectContent>
              {recurrenceOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <InputError message={form.errors.is_repeated} />
        </div>
        {form.data.is_repeated !== 'never' && (
          <div className="grid gap-2">
            <Label htmlFor="repeat_until">Repeat Until</Label>
            <Input
              id="repeat_until"
              type="date"
              value={form.data.repeat_until}
              onChange={(e) => form.setData('repeat_until', e.target.value)}
            />
            <InputError message={form.errors.repeat_until} />
          </div>
        )}
      </fieldset>

      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">Linked meetings</legend>
        <MeetingMultiSelect
          options={meetingOptions}
          value={form.data.meeting_ids}
          onChange={(ids) => form.setData('meeting_ids', ids)}
          error={form.errors.meeting_ids}
        />
      </fieldset>

      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">Media</legend>
        <div className="grid gap-2">
          <Label htmlFor="cover">Cover Image</Label>
          <Input
            id="cover"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onChange={(e) =>
              form.setData('cover', e.target.files?.[0] ?? null)
            }
          />
          {coverPreview && (
            <img
              src={coverPreview}
              alt="Cover preview"
              className="h-32 w-full max-w-xs rounded-md object-cover"
            />
          )}
          <InputError message={form.errors.cover} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="gallery">Gallery (images or videos)</Label>
          <Input
            ref={galleryInputRef}
            id="gallery"
            type="file"
            accept="image/jpeg,image/png,image/webp,video/mp4"
            multiple
            onChange={(e) => {
              const files = Array.from(e.target.files ?? [])
              if (files.length > 0) {
                handleGalleryAdd(files)
              }
              if (galleryInputRef.current) {
                galleryInputRef.current.value = ''
              }
            }}
          />
          <InputError message={form.errors.gallery} />
        </div>
        {pendingGallery.length > 0 && (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {pendingGallery.map((item, index) => (
              <div
                key={`pending-${index}`}
                className="relative rounded-md border border-dashed p-2"
              >
                {item.file.type.startsWith('video/') ? (
                  <video
                    src={item.previewUrl}
                    className="aspect-video w-full rounded object-cover"
                  />
                ) : (
                  <img
                    src={item.previewUrl}
                    alt={item.file.name}
                    className="aspect-video w-full rounded object-cover"
                  />
                )}
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  className="absolute top-1 end-1 size-6"
                  onClick={() => handleRemovePendingGalleryItem(index)}
                >
                  <X className="size-3" />
                </Button>
              </div>
            ))}
          </div>
        )}
        {existingGallery.length > 0 && (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {existingGallery.map((item) => (
              <div key={item.id} className="relative rounded-md border p-2">
                {item.mime_type.startsWith('video/') ? (
                  <video
                    src={item.url}
                    controls
                    className="aspect-video w-full rounded object-cover"
                  />
                ) : (
                  <img
                    src={item.url}
                    alt="Gallery item"
                    className="aspect-video w-full rounded object-cover"
                  />
                )}
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  className="absolute top-1 end-1 size-6"
                  onClick={() => handleRemoveGalleryItem(item.id)}
                >
                  <X className="size-3" />
                </Button>
              </div>
            ))}
          </div>
        )}
      </fieldset>

      <fieldset className="space-y-4 rounded-lg border p-4">
        <legend className="px-1 text-sm font-medium">SEO</legend>
        <div className="grid gap-2">
          <Label htmlFor="meta_title_ar">Meta title (Arabic)</Label>
          <Input
            id="meta_title_ar"
            dir="rtl"
            value={form.data.meta_title_ar}
            onChange={(e) => form.setData('meta_title_ar', e.target.value)}
          />
          <InputError message={form.errors.meta_title_ar} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="meta_title_en">Meta title (English)</Label>
          <Input
            id="meta_title_en"
            value={form.data.meta_title_en}
            onChange={(e) => form.setData('meta_title_en', e.target.value)}
          />
          <InputError message={form.errors.meta_title_en} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="meta_description_ar">Meta description (Arabic)</Label>
          <Textarea
            id="meta_description_ar"
            dir="rtl"
            value={form.data.meta_description_ar}
            onChange={(e) =>
              form.setData('meta_description_ar', e.target.value)
            }
          />
          <InputError message={form.errors.meta_description_ar} />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="meta_description_en">Meta description (English)</Label>
          <Textarea
            id="meta_description_en"
            value={form.data.meta_description_en}
            onChange={(e) =>
              form.setData('meta_description_en', e.target.value)
            }
          />
          <InputError message={form.errors.meta_description_en} />
        </div>
      </fieldset>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="outline" asChild>
          <Link href={cancelUrl}>Cancel</Link>
        </Button>
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Saving…' : submitLabel}
        </Button>
      </div>
    </form>
  )
}
