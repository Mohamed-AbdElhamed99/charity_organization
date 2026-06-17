import { useState } from 'react'
import { Head, useForm, usePage } from '@inertiajs/react'
import { route } from 'ziggy-js'
import { LocaleFieldTabs } from '@/components/admin/locale-field-tabs'
import InputError from '@/components/input-error'
import { RichTextEditor } from '@/components/rich-text-editor/rich-text-editor'
import { Main } from '@/components/layout/main'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { type LegalDocument, type LegalDocumentType } from '@/types/models/legal-document'

type PageProps = {
  document: LegalDocument
  documentType: LegalDocumentType
  documentLabel: string
}

const updateRoutes: Record<LegalDocumentType, string> = {
  terms: 'admin.legal.terms.update',
  privacy: 'admin.legal.privacy.update',
}

export default function LegalDocumentEdit() {
  const { document, documentType, documentLabel } = usePage<PageProps>().props
  const [activeLocale, setActiveLocale] = useState<'ar' | 'en'>('ar')

  const form = useForm({
    title_ar: document.title_ar,
    title_en: document.title_en ?? '',
    body_ar: document.body_ar,
    body_en: document.body_en ?? '',
  })

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    form.patch(route(updateRoutes[documentType]), { preserveScroll: true })
  }

  return (
    <>
      <Head title={documentLabel} />

      <Main className="flex flex-1 flex-col gap-4 sm:gap-6">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">{documentLabel}</h2>
          <p className="text-muted-foreground">
            Edit the bilingual content for this legal document.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="max-w-3xl space-y-6">
          <LocaleFieldTabs
            activeLocale={activeLocale}
            onLocaleChange={setActiveLocale}
          />

          {activeLocale === 'ar' ? (
            <div className="space-y-4">
              <div className="grid gap-2">
                <Label htmlFor="title_ar">Title (Arabic)</Label>
                <Input
                  id="title_ar"
                  value={form.data.title_ar}
                  onChange={(event) =>
                    form.setData('title_ar', event.target.value)
                  }
                  dir="rtl"
                  required
                />
                <InputError message={form.errors.title_ar} />
              </div>
              <div className="grid gap-2">
                <Label>Body (Arabic)</Label>
                <RichTextEditor
                  value={form.data.body_ar}
                  onChange={(html) => form.setData('body_ar', html)}
                  dir="rtl"
                  minHeight="16rem"
                />
                <InputError message={form.errors.body_ar} />
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="grid gap-2">
                <Label htmlFor="title_en">Title (English)</Label>
                <Input
                  id="title_en"
                  value={form.data.title_en}
                  onChange={(event) =>
                    form.setData('title_en', event.target.value)
                  }
                  dir="ltr"
                />
                <InputError message={form.errors.title_en} />
              </div>
              <div className="grid gap-2">
                <Label>Body (English)</Label>
                <RichTextEditor
                  value={form.data.body_en}
                  onChange={(html) => form.setData('body_en', html)}
                  dir="ltr"
                  minHeight="16rem"
                />
                <InputError message={form.errors.body_en} />
              </div>
            </div>
          )}

          <Button type="submit" disabled={form.processing}>
            Save changes
          </Button>
        </form>
      </Main>
    </>
  )
}

LegalDocumentEdit.layout = {
  breadcrumbs: [
    {
      title: 'Legal Documents',
      href: route('admin.legal.terms.edit'),
    },
  ],
}
