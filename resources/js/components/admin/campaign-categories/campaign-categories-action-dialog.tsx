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
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { type CampaignCategory } from '@/types/models/campaign-category'

type CampaignCategoriesActionDialogProps = {
  currentRow?: CampaignCategory
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function CampaignCategoriesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: CampaignCategoriesActionDialogProps) {
  const isEdit = !!currentRow

  const form = useForm({
    name_ar: currentRow?.name_ar ?? '',
    name_en: currentRow?.name_en ?? '',
    description: currentRow?.description ?? '',
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
      description: currentRow?.description ?? '',
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
      form.patch(route('admin.campaign-categories.update', currentRow.id), options)
      return
    }

    form.post(route('admin.campaign-categories.store'), options)
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
              ? 'Update the campaign category details.'
              : 'Create a new campaign category.'}
          </DialogDescription>
        </DialogHeader>

        <form
          id="campaign-category-form"
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

          <div className="grid gap-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={form.data.description}
              onChange={(event) =>
                form.setData('description', event.target.value)
              }
              rows={3}
            />
            <InputError message={form.errors.description} />
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
            form="campaign-category-form"
            disabled={form.processing}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
