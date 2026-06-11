import { useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { store, update } from '@/routes/admin/beneficiaries/assessments'
import type { BeneficiaryAssessment } from '@/types/models/beneficiary'

type AssessmentActionSheetProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  beneficiaryId: number
  assessment?: BeneficiaryAssessment | null
  canReview?: boolean
}

function todayDateInputValue(): string {
  return new Date().toISOString().slice(0, 10)
}

export function AssessmentActionSheet({
  open,
  onOpenChange,
  beneficiaryId,
  assessment,
  canReview = false,
}: AssessmentActionSheetProps) {
  const isEdit = Boolean(assessment)

  const form = useForm({
    assessment_date: assessment?.assessment_date ?? todayDateInputValue(),
    purpose: assessment?.purpose ?? '',
    researcher_opinion: assessment?.researcher_opinion ?? '',
    recommended_aid_amount:
      assessment?.recommended_aid_amount?.toString() ?? '',
    status: assessment?.status ?? 'pending',
    rejection_reason: assessment?.rejection_reason ?? '',
  })

  useEffect(() => {
    if (!open) {
      return
    }

    form.clearErrors()
    form.setData({
      assessment_date: assessment?.assessment_date ?? todayDateInputValue(),
      purpose: assessment?.purpose ?? '',
      researcher_opinion: assessment?.researcher_opinion ?? '',
      recommended_aid_amount:
        assessment?.recommended_aid_amount?.toString() ?? '',
      status: assessment?.status ?? 'pending',
      rejection_reason: assessment?.rejection_reason ?? '',
    })
  }, [open, assessment])

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    form.transform((data) => ({
      ...data,
      recommended_aid_amount:
        data.recommended_aid_amount === ''
          ? null
          : Number(data.recommended_aid_amount),
    }))

    const options = {
      preserveScroll: true,
      only: ['assessments', 'beneficiary'],
      onSuccess: () => onOpenChange(false),
    }

    if (isEdit && assessment) {
      form.put(update.url({ beneficiary: beneficiaryId, assessment: assessment.id }), options)
      return
    }

    form.post(store.url(beneficiaryId), options)
  }

  return (
    <Sheet
      open={open}
      onOpenChange={(state) => {
        if (!state) {
          form.reset()
          form.clearErrors()
        }
        onOpenChange(state)
      }}
    >
      <SheetContent className="flex w-full flex-col sm:max-w-xl">
        <SheetHeader className="text-start">
          <SheetTitle>
            {isEdit ? 'Edit Assessment' : 'New Assessment'}
          </SheetTitle>
          <SheetDescription>
            Record a social investigation assessment for this beneficiary.
          </SheetDescription>
        </SheetHeader>

        <form
          id="assessment-form"
          onSubmit={handleSubmit}
          className="flex flex-1 flex-col gap-4 overflow-y-auto py-4"
        >
          <div className="grid gap-2">
            <Label htmlFor="assessment_date">Assessment date</Label>
            <Input
              id="assessment_date"
              type="date"
              value={form.data.assessment_date}
              onChange={(event) =>
                form.setData('assessment_date', event.target.value)
              }
            />
            <InputError message={form.errors.assessment_date} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="purpose">Purpose</Label>
            <Textarea
              id="purpose"
              value={form.data.purpose}
              onChange={(event) => form.setData('purpose', event.target.value)}
              rows={2}
            />
            <InputError message={form.errors.purpose} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="researcher_opinion">Researcher opinion</Label>
            <Textarea
              id="researcher_opinion"
              value={form.data.researcher_opinion}
              onChange={(event) =>
                form.setData('researcher_opinion', event.target.value)
              }
              rows={4}
            />
            <InputError message={form.errors.researcher_opinion} />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="recommended_aid_amount">
              Recommended aid amount
            </Label>
            <Input
              id="recommended_aid_amount"
              type="number"
              min="0"
              step="0.01"
              value={form.data.recommended_aid_amount}
              onChange={(event) =>
                form.setData('recommended_aid_amount', event.target.value)
              }
              dir="ltr"
            />
            <InputError message={form.errors.recommended_aid_amount} />
          </div>

          {isEdit && canReview && (
            <>
              <div className="grid gap-2">
                <Label htmlFor="status">Review status</Label>
                <Select
                  value={form.data.status}
                  onValueChange={(value) => form.setData('status', value)}
                >
                  <SelectTrigger id="status">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.status} />
              </div>

              {form.data.status === 'rejected' && (
                <div className="grid gap-2">
                  <Label htmlFor="rejection_reason">Rejection reason</Label>
                  <Textarea
                    id="rejection_reason"
                    value={form.data.rejection_reason}
                    onChange={(event) =>
                      form.setData('rejection_reason', event.target.value)
                    }
                    rows={3}
                  />
                  <InputError message={form.errors.rejection_reason} />
                </div>
              )}
            </>
          )}
        </form>

        <SheetFooter className="px-0">
          <Button
            type="submit"
            form="assessment-form"
            disabled={form.processing}
          >
            {form.processing ? 'Saving...' : isEdit ? 'Update' : 'Create'}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
