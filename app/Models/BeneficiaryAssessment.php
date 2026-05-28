<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BeneficiaryAssessment extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    // ─── Media Collections ───────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        // Assessment supporting documents: ID copies, photos, reports
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'status'                  => AssessmentStatus::class,
            'assessment_date'         => 'date',
            'reviewed_at'             => 'datetime',
            'housing_details'         => 'array',
            'economic_details'        => 'array',
            'health_details'          => 'array',
            'family_details'          => 'array',
            'recommended_aid_amount'  => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', AssessmentStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AssessmentStatus::Approved);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', AssessmentStatus::Rejected);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === AssessmentStatus::Pending,
        );
    }

    protected function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === AssessmentStatus::Approved,
        );
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Approve assessment and activate the beneficiary.
     * Wrapped in a transaction to ensure both records update atomically.
     */
    public function approve(User $reviewer): void
    {
        \DB::transaction(function () use ($reviewer) {
            $this->update([
                'status'      => AssessmentStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->beneficiary->update([
                'status' => BeneficiaryStatus::Active,
            ]);
        });
    }

    /**
     * Reject assessment with a reason.
     */
    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status'           => AssessmentStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by'      => $reviewer->id,
            'reviewed_at'      => now(),
        ]);
    }
}
