import { useRef } from 'react'
import { X } from 'lucide-react'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { NewsGalleryItem } from '@/types/models/news'

type PendingGalleryItem = {
  file: File
  previewUrl: string
}

type NewsFormMediaSectionProps = {
  thumbnailPreview: string | null
  mainMediaPreview: string | null
  existingGallery: NewsGalleryItem[]
  pendingGallery: PendingGalleryItem[]
  errors: Record<string, string | undefined>
  onThumbnailChange: (file: File | null) => void
  onMainMediaChange: (file: File | null) => void
  onGalleryAdd: (files: File[]) => void
  onRemovePendingGalleryItem: (index: number) => void
  onRemoveGalleryItem: (id: number) => void
}

export type { PendingGalleryItem }

export function NewsFormMediaSection({
  thumbnailPreview,
  mainMediaPreview,
  existingGallery,
  pendingGallery,
  errors,
  onThumbnailChange,
  onMainMediaChange,
  onGalleryAdd,
  onRemovePendingGalleryItem,
  onRemoveGalleryItem,
}: NewsFormMediaSectionProps) {
  const galleryInputRef = useRef<HTMLInputElement>(null)

  const handleGalleryChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(event.target.files ?? [])
    if (files.length > 0) {
      onGalleryAdd(files)
    }
    // Reset so the same files can be re-selected if needed
    if (galleryInputRef.current) {
      galleryInputRef.current.value = ''
    }
  }

  return (
    <fieldset className="space-y-4 rounded-lg border p-4">
      <legend className="px-1 text-sm font-medium">Media</legend>

      <div className="grid gap-2">
        <Label htmlFor="thumbnail">Thumbnail</Label>
        <Input
          id="thumbnail"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          onChange={(event) =>
            onThumbnailChange(event.target.files?.[0] ?? null)
          }
        />
        {thumbnailPreview && (
          <img
            src={thumbnailPreview}
            alt="Thumbnail preview"
            className="h-24 w-24 rounded-md object-cover"
          />
        )}
        <InputError message={errors.thumbnail} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="main_media">Main Media (image or video)</Label>
        <Input
          id="main_media"
          type="file"
          accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
          onChange={(event) =>
            onMainMediaChange(event.target.files?.[0] ?? null)
          }
        />
        {mainMediaPreview && (
          mainMediaPreview.match(/\.(mp4|webm)$/i) ? (
            <video
              src={mainMediaPreview}
              controls
              className="max-h-40 max-w-full rounded-md"
            />
          ) : (
            <img
              src={mainMediaPreview}
              alt="Main media preview"
              className="max-h-40 max-w-full rounded-md object-cover"
            />
          )
        )}
        <InputError message={errors.main_media} />
      </div>

      <div className="grid gap-2">
        <Label htmlFor="gallery">
          Gallery (images or videos)
          {pendingGallery.length > 0 && (
            <span className="ml-2 text-muted-foreground">
              — {pendingGallery.length} queued
            </span>
          )}
        </Label>
        <Input
          ref={galleryInputRef}
          id="gallery"
          type="file"
          accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
          multiple
          onChange={handleGalleryChange}
        />
        <p className="text-xs text-muted-foreground">
          Select multiple files at once, or pick again to add more.
        </p>
        <InputError message={errors.gallery} />
      </div>

      {pendingGallery.length > 0 && (
        <div className="grid gap-2">
          <p className="text-xs font-medium text-muted-foreground">
            Queued for upload ({pendingGallery.length})
          </p>
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
                <p className="mt-1 truncate text-xs text-muted-foreground">
                  {item.file.name}
                </p>
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  className="absolute top-1 right-1 size-6"
                  onClick={() => onRemovePendingGalleryItem(index)}
                  aria-label={`Remove ${item.file.name}`}
                >
                  <X className="size-3" />
                </Button>
              </div>
            ))}
          </div>
        </div>
      )}

      {existingGallery.length > 0 && (
        <div className="grid gap-2">
          <p className="text-xs font-medium text-muted-foreground">
            Saved ({existingGallery.length})
          </p>
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
                  className="absolute top-1 right-1 size-6"
                  onClick={() => onRemoveGalleryItem(item.id)}
                  aria-label="Remove gallery item"
                >
                  <X className="size-3" />
                </Button>
              </div>
            ))}
          </div>
        </div>
      )}
    </fieldset>
  )
}
