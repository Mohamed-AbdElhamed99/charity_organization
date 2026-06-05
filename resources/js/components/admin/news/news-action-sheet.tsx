import { useEffect, useMemo, useState } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import type { News, NewsCategory, NewsGalleryItem } from '@/types/models/news'
import { NewsFormLocaleSection } from './news-form-locale-section'
import { NewsFormMediaSection, type PendingGalleryItem } from './news-form-media-section'
import { NewsFormSeoSection } from './news-form-seo-section'
import { NewsFormSettingsSection } from './news-form-settings-section'

type NewsActionSheetProps = {
  currentRow?: News
  open: boolean
  onOpenChange: (open: boolean) => void
  categories: NewsCategory[]
}

function buildInitialFormData(currentRow?: News) {
  return {
    title_ar: currentRow?.title_ar ?? '',
    title_en: currentRow?.title_en ?? '',
    slug: currentRow?.slug ?? '',
    subtitle_ar: currentRow?.subtitle_ar ?? '',
    subtitle_en: currentRow?.subtitle_en ?? '',
    excerpt_ar: currentRow?.excerpt_ar ?? '',
    excerpt_en: currentRow?.excerpt_en ?? '',
    body_ar: currentRow?.body_ar ?? '',
    body_en: currentRow?.body_en ?? '',
    video_url: currentRow?.video_url ?? '',
    category_id: currentRow?.category_id ? String(currentRow.category_id) : '',
    published_at: currentRow?.published_at ?? '',
    is_active: currentRow?.is_active ?? true,
    is_private: currentRow?.is_private ?? false,
    meta_title_ar: currentRow?.meta_title_ar ?? '',
    meta_title_en: currentRow?.meta_title_en ?? '',
    meta_description_ar: currentRow?.meta_description_ar ?? '',
    meta_description_en: currentRow?.meta_description_en ?? '',
    thumbnail: null as File | null,
    main_media: null as File | null,
    gallery: [] as File[],
    removed_gallery_ids: [] as number[],
  }
}

export function NewsActionSheet({
  currentRow,
  open,
  onOpenChange,
  categories,
}: NewsActionSheetProps) {
  const isEdit = !!currentRow
  const [existingGallery, setExistingGallery] = useState<NewsGalleryItem[]>([])
  const [pendingGallery, setPendingGallery] = useState<PendingGalleryItem[]>([])

  const form = useForm(buildInitialFormData(currentRow))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(buildInitialFormData(currentRow))
    setExistingGallery(currentRow?.gallery ?? [])
    setPendingGallery([])
  }, [open, currentRow])

  const thumbnailPreview = useMemo(() => {
    if (form.data.thumbnail) {
      return URL.createObjectURL(form.data.thumbnail)
    }

    return currentRow?.thumbnail || null
  }, [form.data.thumbnail, currentRow?.thumbnail])

  const mainMediaPreview = useMemo(() => {
    if (form.data.main_media) {
      return URL.createObjectURL(form.data.main_media)
    }

    return currentRow?.main_media || null
  }, [form.data.main_media, currentRow?.main_media])

  useEffect(() => {
    return () => {
      if (form.data.thumbnail && thumbnailPreview?.startsWith('blob:')) {
        URL.revokeObjectURL(thumbnailPreview)
      }
      if (form.data.main_media && mainMediaPreview?.startsWith('blob:')) {
        URL.revokeObjectURL(mainMediaPreview)
      }
    }
  }, [form.data.thumbnail, form.data.main_media, thumbnailPreview, mainMediaPreview])

  const handleGalleryAdd = (files: File[]) => {
    const newItems = files.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }))
    const updated = [...pendingGallery, ...newItems]
    setPendingGallery(updated)
    form.setData('gallery', updated.map((item) => item.file))
  }

  const handleRemovePendingGalleryItem = (index: number) => {
    const item = pendingGallery[index]
    URL.revokeObjectURL(item.previewUrl)
    const updated = pendingGallery.filter((_, i) => i !== index)
    setPendingGallery(updated)
    form.setData('gallery', updated.map((item) => item.file))
  }

  const handleRemoveGalleryItem = (id: number) => {
    setExistingGallery((items) => items.filter((item) => item.id !== id))
    form.setData('removed_gallery_ids', [...form.data.removed_gallery_ids, id])
  }

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    form.transform((data) => ({
      ...data,
      category_id: data.category_id ? Number(data.category_id) : null,
      is_active: data.is_active ? 1 : 0,
      is_private: data.is_private ? 1 : 0,
    }))

    if (isEdit && currentRow) {
      form.patch(route('admin.news.update', currentRow.id), options)
      return
    }

    form.post(route('admin.news.store'), options)
  }

  return (
    <Sheet
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
          pendingGallery.forEach((item) => URL.revokeObjectURL(item.previewUrl))
          setPendingGallery([])
        }
        onOpenChange(state)
      }}
    >
      <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
        <SheetHeader className="text-start">
          <SheetTitle>{isEdit ? 'Edit News' : 'Add News'}</SheetTitle>
          <SheetDescription>
            {isEdit
              ? 'Update the news article details.'
              : 'Create a new news article.'}
          </SheetDescription>
        </SheetHeader>

        <form
          id="news-form"
          onSubmit={handleSubmit}
          className="space-y-4 px-4 pb-4"
        >
          <NewsFormLocaleSection
            locale="ar"
            title={form.data.title_ar}
            subtitle={form.data.subtitle_ar}
            excerpt={form.data.excerpt_ar}
            body={form.data.body_ar}
            errors={form.errors}
            onTitleChange={(value) => form.setData('title_ar', value)}
            onSubtitleChange={(value) => form.setData('subtitle_ar', value)}
            onExcerptChange={(value) => form.setData('excerpt_ar', value)}
            onBodyChange={(value) => form.setData('body_ar', value)}
          />

          <NewsFormLocaleSection
            locale="en"
            title={form.data.title_en}
            subtitle={form.data.subtitle_en}
            excerpt={form.data.excerpt_en}
            body={form.data.body_en}
            errors={form.errors}
            onTitleChange={(value) => form.setData('title_en', value)}
            onSubtitleChange={(value) => form.setData('subtitle_en', value)}
            onExcerptChange={(value) => form.setData('excerpt_en', value)}
            onBodyChange={(value) => form.setData('body_en', value)}
          />

          <NewsFormSettingsSection
            slug={form.data.slug}
            categoryId={form.data.category_id}
            videoUrl={form.data.video_url}
            publishedAt={form.data.published_at}
            isActive={form.data.is_active}
            isPrivate={form.data.is_private}
            categories={categories}
            errors={form.errors}
            isEdit={isEdit}
            onSlugChange={(value) => form.setData('slug', value)}
            onCategoryChange={(value) => form.setData('category_id', value)}
            onVideoUrlChange={(value) => form.setData('video_url', value)}
            onPublishedAtChange={(value) => form.setData('published_at', value)}
            onIsActiveChange={(value) => form.setData('is_active', value)}
            onIsPrivateChange={(value) => form.setData('is_private', value)}
          />

          <NewsFormMediaSection
            thumbnailPreview={thumbnailPreview}
            mainMediaPreview={mainMediaPreview}
            existingGallery={existingGallery}
            pendingGallery={pendingGallery}
            errors={form.errors}
            onThumbnailChange={(file) => form.setData('thumbnail', file)}
            onMainMediaChange={(file) => form.setData('main_media', file)}
            onGalleryAdd={handleGalleryAdd}
            onRemovePendingGalleryItem={handleRemovePendingGalleryItem}
            onRemoveGalleryItem={handleRemoveGalleryItem}
          />

          <NewsFormSeoSection
            metaTitleAr={form.data.meta_title_ar}
            metaTitleEn={form.data.meta_title_en}
            metaDescriptionAr={form.data.meta_description_ar}
            metaDescriptionEn={form.data.meta_description_en}
            errors={form.errors}
            onMetaTitleArChange={(value) => form.setData('meta_title_ar', value)}
            onMetaTitleEnChange={(value) => form.setData('meta_title_en', value)}
            onMetaDescriptionArChange={(value) =>
              form.setData('meta_description_ar', value)
            }
            onMetaDescriptionEnChange={(value) =>
              form.setData('meta_description_en', value)
            }
          />
        </form>

        <SheetFooter className="px-4 pb-4">
          <Button type="submit" form="news-form" disabled={form.processing}>
            Save changes
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
