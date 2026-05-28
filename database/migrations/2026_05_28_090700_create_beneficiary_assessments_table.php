<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary Assessments (formerly 'case_searches')
 *
 * The social investigation / intake form that must be completed and approved
 * BEFORE a beneficiary becomes active. This is the pre-approval stage.
 *
 * Lifecycle:
 *   1. Staff creates beneficiary record (status: pending_assessment)
 *   2. Field worker fills assessment form
 *   3. Super admin reviews → approves or rejects
 *   4. On approval: beneficiary.status → active
 *   5. On rejection: assessment.status → rejected with reason stored
 *
 * Complex nested details (housing, economic, health) are stored as JSON
 * for Phase 1 to avoid over-engineering. Can be normalized later if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete()
                ->comment('The beneficiary being assessed');

            // Assessment metadata
            $table->foreignId('assessed_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Field worker / staff who conducted the assessment');

            $table->date('assessment_date')
                ->comment('Date the field assessment was conducted');

            $table->text('purpose')
                ->nullable()
                ->comment('Why the beneficiary needs support e.g. Medical, Housing, Food');

            // Detailed assessment sections stored as JSON (Phase 1 approach)
            $table->json('housing_details')
                ->nullable()
                ->comment('Housing type, ownership, rooms, water source, bathroom, condition etc.');

            $table->json('economic_details')
                ->nullable()
                ->comment('Income sources, debts, assets, monthly expenses etc.');

            $table->json('health_details')
                ->nullable()
                ->comment('Medical conditions, disabilities, medications, hospital history etc.');

            $table->json('family_details')
                ->nullable()
                ->comment('Marital status of household members, dependents, orphan status etc.');

            // Researcher opinion and summary
            $table->text('researcher_opinion')
                ->nullable()
                ->comment('Field worker overall assessment and recommendation');

            $table->float('recommended_aid_amount', 10, 2)
                ->nullable()
                ->comment('Recommended financial aid amount based on assessment');

            // Review workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->comment('pending: awaiting review; approved: beneficiary activated; rejected: not approved');

            $table->text('rejection_reason')
                ->nullable()
                ->comment('Required when status = rejected');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who approved or rejected this assessment');

            $table->timestamp('reviewed_at')
                ->nullable()
                ->comment('Timestamp when the review decision was made');

            // Supporting documents stored via Spatie Media Library (media table)
            // No file columns needed here — use media morphs on this model

            $table->timestamps();
            $table->softDeletes();

            // Indexes for workflow queries
            $table->index('status');
            $table->index('assessment_date');
            $table->index(['beneficiary_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_assessments');
    }
};
