<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactUs extends Model
{
    use SoftDeletes , HasFactory;

    protected $table = 'contact_us';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeUnreviewed($query)
    {
        return $query->where('is_reviewed', false);
    }

    public function scopeReviewed($query)
    {
        return $query->where('is_reviewed', true);
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    public function markReviewed(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'is_reviewed'   => true,
            'reviewed_by'   => $reviewer->id,
            'reviewed_at'   => now(),
            'review_notes'  => $notes,
        ]);
    }
}