import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/input-error'
import { type NewsCategory } from '@/types/models/news-category'

type NewsCategoriesActionDialogProps = {
  currentRow?: NewsCategory
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function NewsCategoriesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: NewsCategoriesActionDialogProps) {
  const isEdit = !!currentRow

  const form = useForm({
    name_ar: currentRow?.name_ar ?? '',
    name_en: currentRow?.name_en ?? '',
    is_active: currentRow?.is_active ?? true,
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      name_ar: currentRow?.name_ar ?? '',
      name_en: currentRow?.name_en ?? '',
      is_active: currentRow?.is_active ?? true,
    })
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.news-categories.update', currentRow.id), options)
      return
    }

    form.post(route('admin.news-categories.store'), options)
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <DialogContent className="sm:max-w-lg">
        <DialogHeader className="text-start">
          <DialogTitle>{isEdit ? 'Edit Category' : 'Add Category'}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the news category details.'
              : 'Create a new news category.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="news-category-form"
          onSubmit={handleSubmit}
          className="space-y-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="name_en">Name (English)</Label>
            <Input
              id="name_en"
              value={form.data.name_en}
              onChange={(event) => form.setData('name_en', event.target.value)}
              required
            />
            <InputError message={form.errors.name_en} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="name_ar">Name (Arabic)</Label>
            <Input
              id="name_ar"
              dir="rtl"
              value={form.data.name_ar}
              onChange={(event) => form.setData('name_ar', event.target.value)}
              required
            />
            <InputError message={form.errors.name_ar} />
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="is_active"
              checked={form.data.is_active}
              onCheckedChange={(checked) =>
                form.setData('is_active', checked === true)
              }
            />
            <Label htmlFor="is_active">Active</Label>
            <InputError message={form.errors.is_active} />
          </div>
        </form>

        <DialogFooter>
          <Button
            type="submit"
            form="news-category-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
