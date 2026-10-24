import { useEffect, useMemo } from 'react'
import { Link, useForm } from '@inertiajs/react'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

export type GiftOptionFormValue = {
  name_en: string
  name_ar: string
  amount: string
}

export type CampaignAppealFormData = {
  id: number
  slug: string
  title_en: string
  title_ar: string
  subtitle_en: string | null
  subtitle_ar: string | null
  gift_options: GiftOptionFormValue[]
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

function emptyGiftOption(): GiftOptionFormValue {
  return { name_en: '', name_ar: '', amount: '' }
}

function buildInitialFormData(appeal?: CampaignAppealFormData) {
  return {
    title_en: appeal?.title_en ?? '',
    title_ar: appeal?.title_ar ?? '',
    slug: appeal?.slug ?? '',
    subtitle_en: appeal?.subtitle_en ?? '',
    subtitle_ar: appeal?.subtitle_ar ?? '',
    gift_options:
      appeal?.gift_options && appeal.gift_options.length > 0
        ? appeal.gift_options.map((option) => ({
            name_en: option.name_en,
            name_ar: option.name_ar,
            amount: option.amount,
          }))
        : [emptyGiftOption()],
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
      gift_options: data.gift_options.map((option) => ({
        name_en: option.name_en,
        name_ar: option.name_ar,
        amount: option.amount ? Number(option.amount) : '',
      })),
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

  const optionErrors = form.errors as Record<string, string>

  const updateOption = (index: number, field: keyof GiftOptionFormValue, value: string) => {
    form.setData(
      'gift_options',
      form.data.gift_options.map((option, optionIndex) =>
        optionIndex === index ? { ...option, [field]: value } : option,
      ),
    )
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

      <fieldset className="space-y-3">
        <legend className="text-sm font-medium">Gift options</legend>
        <p className="text-muted-foreground text-sm">
          Each choice has its own name and price, such as Medium Turkey at $30 and Halal Turkey at $50.
        </p>
        <InputError message={optionErrors.gift_options} />
        <div className="space-y-4">
          {form.data.gift_options.map((option, index) => (
            <div key={index} className="grid gap-3 rounded-lg border p-3 md:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor={`gift-option-${index}-name-en`}>Name (English)</Label>
                <Input
                  id={`gift-option-${index}-name-en`}
                  value={option.name_en}
                  onChange={(event) => updateOption(index, 'name_en', event.target.value)}
                  placeholder="Medium Turkey"
                />
                <InputError message={optionErrors[`gift_options.${index}.name_en`]} />
              </div>
              <div className="space-y-2">
                <Label htmlFor={`gift-option-${index}-name-ar`}>Name (Arabic)</Label>
                <Input
                  id={`gift-option-${index}-name-ar`}
                  dir="rtl"
                  value={option.name_ar}
                  onChange={(event) => updateOption(index, 'name_ar', event.target.value)}
                  placeholder="ديك رومي متوسط"
                />
                <InputError message={optionErrors[`gift_options.${index}.name_ar`]} />
              </div>
              <div className="space-y-2">
                <Label htmlFor={`gift-option-${index}-amount`}>Price (USD)</Label>
                <Input
                  id={`gift-option-${index}-amount`}
                  type="number"
                  min="0.01"
                  step="0.01"
                  value={option.amount}
                  onChange={(event) => updateOption(index, 'amount', event.target.value)}
                  placeholder="30"
                />
                <InputError message={optionErrors[`gift_options.${index}.amount`]} />
              </div>
              {form.data.gift_options.length > 1 ? (
                <div className="md:col-span-3">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      form.setData(
                        'gift_options',
                        form.data.gift_options.filter((_, optionIndex) => optionIndex !== index),
                      )
                    }
                  >
                    Remove option
                  </Button>
                </div>
              ) : null}
            </div>
          ))}
        </div>
        {form.data.gift_options.length < 6 ? (
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() =>
              form.setData('gift_options', [...form.data.gift_options, emptyGiftOption()])
            }
          >
            Add option
          </Button>
        ) : null}
      </fieldset>

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
