import { useState } from 'react'
import { ChevronDown, ChevronUp, Pencil, Plus } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  assessmentStatusColors,
  formatAmount,
  formatDate,
} from './data/data'
import type { BeneficiaryAssessment } from '@/types/models/beneficiary'
import { AssessmentActionSheet } from './assessment-action-sheet'

type AssessmentsTimelineProps = {
  beneficiaryId: number
  assessments: BeneficiaryAssessment[]
  canCreate: boolean
  canReview?: boolean
}

export function AssessmentsTimeline({
  beneficiaryId,
  assessments,
  canCreate,
  canReview = false,
}: AssessmentsTimelineProps) {
  const [sheetOpen, setSheetOpen] = useState(false)
  const [editingAssessment, setEditingAssessment] =
    useState<BeneficiaryAssessment | null>(null)
  const [expandedId, setExpandedId] = useState<number | null>(null)

  const openCreate = () => {
    setEditingAssessment(null)
    setSheetOpen(true)
  }

  const openEdit = (assessment: BeneficiaryAssessment) => {
    setEditingAssessment(assessment)
    setSheetOpen(true)
  }

  return (
    <>
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold">Assessments</h3>
          {canCreate && (
            <Button size="sm" onClick={openCreate}>
              <Plus className="me-2 size-4" />
              New Assessment
            </Button>
          )}
        </div>

        {assessments.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No assessments recorded yet.
          </p>
        ) : (
          <div className="space-y-3">
            {assessments.map((assessment) => {
              const expanded = expandedId === assessment.id
              const statusColor = assessmentStatusColors.get(assessment.status)

              return (
                <div
                  key={assessment.id}
                  className="rounded-lg border p-4"
                >
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium">
                          {formatDate(assessment.assessment_date)}
                        </p>
                        <Badge
                          variant="outline"
                          className={cn('capitalize', statusColor)}
                        >
                          {assessment.status_label}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        Assessor: {assessment.assessor?.name ?? '—'}
                      </p>
                      {assessment.recommended_aid_amount !== null && (
                        <p className="text-sm">
                          Recommended aid:{' '}
                          {formatAmount(assessment.recommended_aid_amount)}
                        </p>
                      )}
                    </div>

                    <div className="flex gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                          setExpandedId(expanded ? null : assessment.id)
                        }
                      >
                        {expanded ? (
                          <ChevronUp className="size-4" />
                        ) : (
                          <ChevronDown className="size-4" />
                        )}
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openEdit(assessment)}
                      >
                        <Pencil className="size-4" />
                      </Button>
                    </div>
                  </div>

                  {expanded && (
                    <div className="mt-4 space-y-2 border-t pt-4 text-sm">
                      {assessment.purpose && (
                        <p>
                          <span className="font-medium">Purpose:</span>{' '}
                          {assessment.purpose}
                        </p>
                      )}
                      {assessment.researcher_opinion && (
                        <p className="whitespace-pre-wrap">
                          <span className="font-medium">Opinion:</span>{' '}
                          {assessment.researcher_opinion}
                        </p>
                      )}
                      {assessment.rejection_reason && (
                        <p className="text-destructive">
                          <span className="font-medium">Rejection:</span>{' '}
                          {assessment.rejection_reason}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </div>

      <AssessmentActionSheet
        open={sheetOpen}
        onOpenChange={setSheetOpen}
        beneficiaryId={beneficiaryId}
        assessment={editingAssessment}
        canReview={canReview}
      />
    </>
  )
}
