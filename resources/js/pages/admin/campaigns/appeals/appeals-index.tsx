import { useState } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import type { CampaignAppealFormData } from '@/components/admin/campaign-appeals/appeal-form'
import { Main } from '@/components/layout/main'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { show as campaignShow, index as campaignsIndex } from '@/routes/admin/campaigns'
import {
  create,
  destroy,
  edit,
} from '@/routes/admin/campaigns/appeals'

type CampaignSummary = {
  id: number
  title_en: string
  title_ar: string
}

type AppealRow = CampaignAppealFormData & {
  unit_amount_cents: number
  share_url: string
}

type PageProps = {
  campaign: CampaignSummary
  appeals: AppealRow[]
}

function copyWithTextarea(text: string): boolean {
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.setAttribute('readonly', '')
  textarea.style.position = 'fixed'
  textarea.style.top = '0'
  textarea.style.left = '-9999px'
  document.body.appendChild(textarea)

  const selection = document.getSelection()
  const previousRange = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null

  textarea.focus()
  textarea.select()
  textarea.setSelectionRange(0, text.length)

  let copied = false
  try {
    copied = document.execCommand('copy')
  } catch {
    copied = false
  }

  document.body.removeChild(textarea)

  if (selection && previousRange) {
    selection.removeAllRanges()
    selection.addRange(previousRange)
  }

  return copied
}

async function writeToClipboard(text: string): Promise<boolean> {
  if (window.isSecureContext && navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(text)

      return true
    } catch {
      // A rejected Clipboard API write still falls through to the textarea copy.
    }
  }

  return copyWithTextarea(text)
}

export default function AppealsIndex() {
  const { campaign, appeals } = usePage<PageProps>().props
  const [copiedAppealId, setCopiedAppealId] = useState<number | null>(null)

  const removeAppeal = (appealId: number) => {
    if (!window.confirm('Remove this appeal?')) {
      return
    }

    router.delete(destroy.url({ campaign: campaign.id, appeal: appealId }))
  }

  const copyLink = async (appealId: number, url: string) => {
    const copied = await writeToClipboard(url)

    if (!copied) {
      toast.error('Could not copy the link')

      return
    }

    toast.success('Link copied')
    setCopiedAppealId(appealId)
    window.setTimeout(() => {
      setCopiedAppealId((current) => (current === appealId ? null : current))
    }, 2000)
  }

  return (
    <>
      <Head title={`${campaign.title_en} — Appeals`} />
      <Main className="flex flex-1 flex-col gap-6">
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="outline" size="sm" asChild>
            <Link href={campaignShow.url(campaign.id)}>
              <ArrowLeft className="me-2 size-4" />
              Back to campaign
            </Link>
          </Button>
          <Button size="sm" asChild>
            <Link href={create.url(campaign.id)}>New appeal</Link>
          </Button>
        </div>

        <div>
          <h2 className="text-2xl font-bold tracking-tight">Campaign appeals</h2>
          <p className="text-muted-foreground">{campaign.title_en}</p>
        </div>

        <div className="rounded-lg border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Appeal</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-end">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {appeals.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} className="text-muted-foreground">
                    No appeals yet. Create one to share a specific gift.
                  </TableCell>
                </TableRow>
              ) : (
                appeals.map((appeal) => (
                  <TableRow key={appeal.id}>
                    <TableCell>
                      <p className="font-medium">{appeal.title_en}</p>
                      <p className="text-muted-foreground text-xs">/give/{appeal.slug}</p>
                    </TableCell>
                    <TableCell>
                      ${appeal.unit_amount} per {appeal.unit_label_en}
                    </TableCell>
                    <TableCell>
                      <Badge variant={appeal.is_active ? 'default' : 'secondary'}>
                        {appeal.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-end">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          type="button"
                          onClick={() => copyLink(appeal.id, appeal.share_url)}
                        >
                          {copiedAppealId === appeal.id ? 'Copied' : 'Copy link'}
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                          <Link href={edit.url({ campaign: campaign.id, appeal: appeal.id })}>
                            Edit
                          </Link>
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          type="button"
                          onClick={() => removeAppeal(appeal.id)}
                        >
                          Remove
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      </Main>
    </>
  )
}

AppealsIndex.layout = {
  breadcrumbs: [
    { title: 'Campaigns', href: campaignsIndex.url() },
    { title: 'Appeals' },
  ],
}
