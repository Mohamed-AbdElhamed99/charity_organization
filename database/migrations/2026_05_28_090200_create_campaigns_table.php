<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaigns (formerly 'activities')
 *
 * The central entity of the system. The org collects donations then runs
 * campaigns/events to deliver aid. Everything financial flows through campaigns.
 *
 * A campaign may:
 *   - Be linked to one or more beneficiaries (or none at all)
 *   - Have a donation target (fundraising) or just an expense budget
 *   - Be public (visible on website) or internal only
 *   - Recur on a schedule
 *
 * Examples from requirements:
 *   - Child cancer case fundraiser        → has beneficiary, has donation target
 *   - House renovation in Al-Qubeiba      → has beneficiary (family), budget-based
 *   - Press conference for Kidney Center  → may have org beneficiary, event-based
 *   - Ramadan orphan celebration          → seasonal, multiple child beneficiaries
 *   - Mansoura University conference      → community event, no beneficiary
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            // Classification
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('campaign_categories')
                ->nullOnDelete()
                ->comment('Campaign category e.g. Medical, Housing, Seasonal');

            // Identity
            $table->string('slug')->unique()->comment('URL-friendly identifier');
            $table->string('title_ar')->comment('Campaign title in Arabic');
            $table->string('title_en')->comment('Campaign title in English');
            $table->text('excerpt_ar')->nullable()->comment('Short description in Arabic for cards/listings');
            $table->text('excerpt_en')->nullable()->comment('Short description in English for cards/listings');
            $table->longText('description_ar')->nullable()->comment('Full campaign description in Arabic');
            $table->longText('description_en')->nullable()->comment('Full campaign description in English');

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable()->comment('GPS latitude for map display');
            $table->decimal('lng', 10, 7)->nullable()->comment('GPS longitude for map display');

            // Financial targets
            $table->float('budget', 2)->default(0)
                ->comment('Approved spending budget for this campaign');
            $table->float('donation_target', 2)->nullable()
                ->comment('Fundraising goal; null means no specific target');

            // Status & visibility
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])
                ->default('draft')
                ->comment('Lifecycle status of the campaign');
            $table->boolean('is_public')->default(false)
                ->comment('Whether this campaign appears on the public website');
            $table->boolean('open_donation_form')->default(false)
                ->comment('Whether the donation form is open for this campaign');

            // Recurrence
            $table->enum('is_repeated', ['never', 'daily', 'weekly', 'monthly'])
                ->default('never')
                ->comment('Recurrence schedule; never = one-time campaign');
            $table->date('repeat_until')->nullable()
                ->comment('Last date to repeat; only relevant when is_repeated != never');

            // SEO
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();

            // Ownership & audit
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff user who created this campaign');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries and reports
            $table->index('status');
            $table->index('is_public');
            $table->index('start_date');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};