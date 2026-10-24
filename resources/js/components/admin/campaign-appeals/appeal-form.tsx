import { useEffect, useMemo } from 'react'
import { Link, useForm } from '@inertiajs/react'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

export type CampaignAppealFormData = {
  id: number
  slug: string
  title_en: string
  title_ar: string
  subtitle_en: string | null
  subtitle_ar: string | null
  unit_amount: string
  unit_label_en: string
  unit_label_ar: string
  unit_label_plural_en: string
  unit_label_plural_ar: string
  quantity_options_text: string
  allow_custom_amount: boolean
  cta_label_en: string
  cta_label_ar: string
  is_active: boolean
  sort_order: number
  hero_url: string | null
}

type AppealFormProps = {
  appeal?: CampaignAppealFormData
  submitUrl: string
  method: 'post' | 'patch'
  submitLabel: string
  cancelUrl: string
}

function buildInitialFormData(appeal?: CampaignAppealFormData) {
  return {
    title_en: appeal?.title_en ?? '',
    title_ar: appeal?.title_ar ?? '',
    slug: appeal?.slug ?? '',
    subtitle_en: appeal?.subtitle_en ?? '',
    subtitle_ar: appeal?.subtitle_ar ?? '',
    unit_amount: appeal?.unit_amount ?? '',
    unit_label_en: appeal?.unit_label_en ?? '',
    unit_label_ar: appeal?.unit_label_ar ?? '',
    unit_label_plural_en: appeal?.unit_label_plural_en ?? '',
    unit_label_plural_ar: appeal?.unit_label_plural_ar ?? '',
    quantity_options: appeal?.quantity_options_text ?? '1, 3, 5',
    allow_custom_amount: appeal?.allow_custom_amount ?? true,
    cta_label_en: appeal?.cta_label_en ?? '',
    cta_label_ar: appeal?.cta_label_ar ?? '',
    is_active: appeal?.is_active ?? true,
    sort_order: appeal?.sort_order != null ? String(appeal.sort_order) : '0',
    hero: null as File | null,
  }
}

export function AppealForm({
  appeal,
  submitUrl,
  method,
  submitLabel,
  cancelUrl,
}: AppealFormProps) {
  const form = useForm(buildInitialFormData(appeal))

  useEffect(() => {
    form.clearErrors()
    form.setData(buildInitialFormData(appeal))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [appeal?.id])

  const heroPreview = useMemo(() => {
    if (form.data.hero) {
      return URL.createObjectURL(form.data.hero)
    }

    return appeal?.hero_url || null
  }, [form.data.hero, appeal?.hero_url])

  useEffect(() => {
    return () => {
      if (form.data.hero && heroPreview?.startsWith('blob:')) {
        URL.revokeObjectURL(heroPreview)
      }
    }
  }, [form.data.hero, heroPreview])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      allow_custom_amount: data.allow_custom_amount ? 1 : 0,
      is_active: data.is_active ? 1 : 0,
      sort_order: data.sort_order ? Number(data.sort_order) : 0,
      unit_amount: data.unit_amount ? Number(data.unit_amount) : '',
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
      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="title_en">Title (English)</Label>
          <Input
            id="title_en"
            value={form.data.title_en}
            onChange={(event) => form.setData('title_en', event.target.value)}
          />
          <InputError message={form.errors.title_en} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="title_ar">Title (Arabic)</Label>
          <Input
            id="title_ar"
            dir="rtl"
            value={form.data.title_ar}
            onChange={(event) => form.setData('title_ar', event.target.value)}
          />
          <InputError message={form.errors.title_ar} />
        </div>
        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="slug">Share slug</Label>
          <Input
            id="slug"
            value={form.data.slug}
            onChange={(event) => form.setData('slug', event.target.value)}
            placeholder="ramadan-food-box"
          />
          <InputError message={form.errors.slug} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="subtitle_en">Subtitle (English)</Label>
          <Textarea
            id="subtitle_en"
            value={form.data.subtitle_en}
            onChange={(event) => form.setData('subtitle_en', event.target.value)}
          />
          <InputError message={form.errors.subtitle_en} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="subtitle_ar">Subtitle (Arabic)</Label>
          <Textarea
            id="subtitle_ar"
            dir="rtl"
            value={form.data.subtitle_ar}
            onChange={(event) => form.setData('subtitle_ar', event.target.value)}
          />
          <InputError message={form.errors.subtitle_ar} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="unit_amount">Price per unit (USD)</Label>
          <Input
            id="unit_amount"
            type="number"
            min="0.01"
            step="0.01"
            value={form.data.unit_amount}
            onChange={(event) => form.setData('unit_amount', event.target.value)}
          />
          <InputError message={form.errors.unit_amount} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="quantity_options">Quantities</Label>
          <Input
            id="quantity_options"
            value={form.data.quantity_options}
            onChange={(event) => form.setData('quantity_options', event.target.value)}
            placeholder="1, 3, 5"
          />
          <InputError message={form.errors.quantity_options} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="unit_label_en">Unit name (English)</Label>
          <Input
            id="unit_label_en"
            value={form.data.unit_label_en}
            onChange={(event) => form.setData('unit_label_en', event.target.value)}
            placeholder="box"
          />
          <InputError message={form.errors.unit_label_en} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="unit_label_plural_en">Unit plural (English)</Label>
          <Input
            id="unit_label_plural_en"
            value={form.data.unit_label_plural_en}
            onChange={(event) =>
              form.setData('unit_label_plural_en', event.target.value)
            }
            placeholder="boxes"
          />
          <InputError message={form.errors.unit_label_plural_en} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="unit_label_ar">Unit name (Arabic)</Label>
          <Input
            id="unit_label_ar"
            dir="rtl"
            value={form.data.unit_label_ar}
            onChange={(event) => form.setData('unit_label_ar', event.target.value)}
          />
          <InputError message={form.errors.unit_label_ar} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="unit_label_plural_ar">Unit plural (Arabic)</Label>
          <Input
            id="unit_label_plural_ar"
            dir="rtl"
            value={form.data.unit_label_plural_ar}
            onChange={(event) =>
              form.setData('unit_label_plural_ar', event.target.value)
            }
          />
          <InputError message={form.errors.unit_label_plural_ar} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="cta_label_en">Button (English)</Label>
          <Input
            id="cta_label_en"
            value={form.data.cta_label_en}
            onChange={(event) => form.setData('cta_label_en', event.target.value)}
            placeholder="Donate a Food Box"
          />
          <InputError message={form.errors.cta_label_en} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="cta_label_ar">Button (Arabic)</Label>
          <Input
            id="cta_label_ar"
            dir="rtl"
            value={form.data.cta_label_ar}
            onChange={(event) => form.setData('cta_label_ar', event.target.value)}
          />
          <InputError message={form.errors.cta_label_ar} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="sort_order">Sort order</Label>
          <Input
            id="sort_order"
            type="number"
            min="0"
            value={form.data.sort_order}
            onChange={(event) => form.setData('sort_order', event.target.value)}
          />
          <InputError message={form.errors.sort_order} />
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="hero">Photo</Label>
        {heroPreview ? (
          <img
            src={heroPreview}
            alt=""
            className="aspect-[4/3] w-full max-w-sm rounded-lg object-cover"
          />
        ) : null}
        <Input
          id="hero"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          onChange={(event) => form.setData('hero', event.target.files?.[0] ?? null)}
        />
        <InputError message={form.errors.hero} />
      </div>

      <div className="flex flex-wrap gap-6">
        <div className="flex items-center gap-2">
          <Checkbox
            id="allow_custom_amount"
            checked={form.data.allow_custom_amount}
            onCheckedChange={(checked) =>
              form.setData('allow_custom_amount', checked === true)
            }
          />
          <Label htmlFor="allow_custom_amount">Allow other amount</Label>
        </div>
        <div className="flex items-center gap-2">
          <Checkbox
            id="is_active"
            checked={form.data.is_active}
            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
          />
          <Label htmlFor="is_active">Active</Label>
        </div>
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={form.processing}>
          {submitLabel}
        </Button>
        <Button variant="outline" asChild>
          <Link href={cancelUrl}>Cancel</Link>
        </Button>
      </div>
    </form>
  )
}
