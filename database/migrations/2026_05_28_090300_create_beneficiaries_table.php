<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiaries — Base Table
 *
 * The polymorphic anchor for all beneficiary types.
 * Three types exist, each with its own profile extension table:
 *   - individual  → beneficiary_individuals
 *   - family      → beneficiary_families
 *   - organization → beneficiary_organizations
 *
 * Status lifecycle:
 *   pending_assessment → approved (active) → inactive
 *
 * A beneficiary must pass through an assessment (beneficiary_assessments)
 * before being approved. Approval moves status from pending_assessment → active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['individual', 'family', 'organization'])
                ->comment('Determines which profile extension table to join');

            $table->string('code')->unique()
                ->comment('Human-readable reference code e.g. BEN-2026-0001');

            $table->enum('status', ['pending_assessment', 'active', 'inactive'])
                ->default('pending_assessment')
                ->comment('pending_assessment: awaiting review; active: approved & receiving aid; inactive: no longer supported');

            $table->text('notes')->nullable()
                ->comment('Internal notes visible only to authorized staff');

            // Audit
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff user who registered this beneficiary');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for filtering and reports
            $table->index('type');
            $table->index('status');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
