<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary Individuals Profile
 *
 * Covers both adult individuals and children (orphans, patients, etc.).
 * subtype distinguishes adults from children and drives which nullable
 * child-specific fields are relevant.
 *
 * Child-specific fields (only populated when subtype = 'child'):
 *   - date_of_father_death
 *   - school_year
 *   - sibling_number
 *   - behavior_notes
 *
 * This table is also reused for family members via beneficiary_family_members.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_individuals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->unique()
                ->constrained('beneficiaries')
                ->cascadeOnDelete()
                ->comment('One-to-one with beneficiaries base table');

            // Subtype
            $table->enum('subtype', ['adult', 'child'])
                ->default('adult')
                ->comment('adult: general individual; child: orphan or minor requiring child-specific fields');

            // Identity
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('national_id')->nullable()->unique()
                ->comment('National ID / passport number');

            // Contact
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();

            // Status fields
            $table->string('health_status')->nullable()
                ->comment('General health description or diagnosed conditions');
            $table->string('education_level')->nullable()
                ->comment('e.g. Primary, Secondary, University, Illiterate');
            $table->string('marital_status')->nullable()
                ->comment('Nullable; not relevant for children');
            $table->string('employment_status')->nullable()
                ->comment('Nullable; not relevant for children');
            $table->float('monthly_income', 2)->nullable()
                ->comment('Monthly income in base currency; null if unemployed or child');

            // Child-specific fields — only populated when subtype = 'child'
            $table->date('date_of_father_death')->nullable()
                ->comment('Child-specific: date the father passed away');
            $table->string('school_year')->nullable()
                ->comment('Child-specific: current school grade/year');
            $table->unsignedTinyInteger('sibling_number')->nullable()
                ->comment('Child-specific: number of siblings');
            $table->text('behavior_notes')->nullable()
                ->comment('Child-specific: behavior at school and with peers');

            $table->text('notes')->nullable()
                ->comment('General notest that may added to beneficieary');
            $table->timestamps();

            // Indexes for search and report queries
            $table->index('subtype');
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_individuals');
    }
};