import { useEffect, useState } from 'react'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { LocaleFieldTabs } from '@/components/admin/locale-field-tabs'
import InputError from '@/components/input-error'
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
import { type Faq } from '@/types/models/faq'

type FaqsActionDialogProps = {
  currentRow?: Faq
  open: boolean
  onOpenChange: (open: boolean) => void
}

function buildInitialFormData(currentRow?: Faq) {
  return {
    question_ar: currentRow?.question_ar ?? '',
    question_en: currentRow?.question_en ?? '',
    answer_ar: currentRow?.answer_ar ?? '',
    answer_en: currentRow?.answer_en ?? '',
    sort_order: currentRow?.sort_order ?? 0,
    is_published: currentRow?.is_published ?? false,
  }
}

export function FaqsActionDialog({
  currentRow,
  open,
  onOpenChange,
}: FaqsActionDialogProps) {
  const isEdit = !!currentRow
  const [activeLocale, setActiveLocale] = useState<'ar' | 'en'>('ar')
  const form = useForm(buildInitialFormData(currentRow))

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData(buildInitialFormData(currentRow))
    setActiveLocale('ar')
  }, [open, currentRow])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const options = {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && currentRow) {
      form.patch(route('admin.faqs.update', currentRow.id), options)
      return
    }

    form.post(route('admin.faqs.store'), options)
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
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader className="text-start">
          <DialogTitle>{isEdit ? 'Edit FAQ' : 'Add FAQ'}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the FAQ entry in Arabic and English.'
              : 'Create a new FAQ entry.'}
          </DialogDescription>
        </DialogHeader>

        <form id="faq-form" onSubmit={handleSubmit} className="space-y-4">
          <div className="flex items-center justify-between gap-2">
            <LocaleFieldTabs
              activeLocale={activeLocale}
              onLocaleChange={setActiveLocale}
            />
          </div>

          {activeLocale === 'ar' ? (
            <div className="space-y-4">
              <div className="grid gap-2">
                <Label htmlFor="question_ar">Question (Arabic)</Label>
                <Input
                  id="question_ar"
                  value={form.data.question_ar}
                  onChange={(event) =>
                    form.setData('question_ar', event.target.value)
                  }
                  dir="rtl"
                  required
                />
                <InputError message={form.errors.question_ar} />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="answer_ar">Answer (Arabic)</Label>
                <Textarea
                  id="answer_ar"
                  value={form.data.answer_ar}
                  onChange={(event) =>
                    form.setData('answer_ar', event.target.value)
                  }
                  className="min-h-32"
                  dir="rtl"
                  required
                />
                <InputError message={form.errors.answer_ar} />
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="grid gap-2">
                <Label htmlFor="question_en">Question (English)</Label>
                <Input
                  id="question_en"
                  value={form.data.question_en}
                  onChange={(event) =>
                    form.setData('question_en', event.target.value)
                  }
                  dir="ltr"
                />
                <InputError message={form.errors.question_en} />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="answer_en">Answer (English)</Label>
                <Textarea
                  id="answer_en"
                  value={form.data.answer_en}
                  onChange={(event) =>
                    form.setData('answer_en', event.target.value)
                  }
                  className="min-h-32"
                  dir="ltr"
                />
                <InputError message={form.errors.answer_en} />
              </div>
            </div>
          )}

          <div className="grid gap-2 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="sort_order">Sort order</Label>
              <Input
                id="sort_order"
                type="number"
                min={0}
                value={form.data.sort_order}
                onChange={(event) =>
                  form.setData('sort_order', Number(event.target.value))
                }
                required
              />
              <InputError message={form.errors.sort_order} />
            </div>
            <div className="flex items-end gap-2 pb-2">
              <Checkbox
                id="is_published"
                checked={form.data.is_published}
                onCheckedChange={(checked) =>
                  form.setData('is_published', checked === true)
                }
              />
              <Label htmlFor="is_published">Published</Label>
              <InputError message={form.errors.is_published} />
            </div>
          </div>
        </form>

        <DialogFooter>
          <Button type="submit" form="faq-form" disabled={form.processing}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
