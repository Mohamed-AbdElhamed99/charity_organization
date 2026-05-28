<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary Families & Family Members
 *
 * A family/household is a beneficiary unit with a head of household
 * and one or more members. Members share the same individual field set
 * (with subtype adult/child) so the schema is consistent.
 *
 * The family head's identity (name, national_id) is stored on
 * beneficiary_families directly for quick lookups and reports.
 * Full member details live in beneficiary_family_members.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Family household profile ---
        Schema::create('beneficiary_families', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->unique()
                ->constrained('beneficiaries')
                ->cascadeOnDelete()
                ->comment('One-to-one with beneficiaries base table');

            // Head of household identity
            $table->string('household_name')
                ->comment('Family name / how the household is known e.g. "Al-Sayyid family"');
            $table->string('national_id')->nullable()
                ->comment('National ID of the head of household');
            $table->string('phone')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->string('village')->nullable()
                ->comment('Village name if below state level');
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();

            // Household profile
            $table->string('social_status')
                ->nullable()
                ->comment('e.g. Widowed, Divorced, Married, Single parent');
            $table->unsignedSmallInteger('total_members')->default(1)
                ->comment('Total number of people in the household including head');
            $table->float('monthly_income', 10, 2)->nullable()
                ->comment('Total household monthly income');

            // Housing
            $table->string('housing_type')->nullable()
                ->comment('e.g. Apartment, House, Rented room, Informal shelter');
            $table->string('housing_ownership')->nullable()
                ->comment('e.g. Owned, Rented, Family-owned, Informal');
            $table->float('monthly_rent', 10, 2)->nullable()
                ->comment('Monthly rent amount if rented');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('household_name');
        });

        // --- Family members ---
        Schema::create('beneficiary_family_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('family_id')
                ->constrained('beneficiary_families')
                ->cascadeOnDelete()
                ->comment('The household this member belongs to');

            // Shared individual fields (mirrors beneficiary_individuals)
            $table->enum('subtype', ['adult', 'child'])
                ->default('adult')
                ->comment('adult or child; drives which nullable fields are relevant');

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('national_id')->nullable();

            // Relation to head of household
            $table->string('relation')
                ->nullable()
                ->comment('e.g. Head, Spouse, Son, Daughter, Dependent');

            // Status
            $table->string('health_status')->nullable();
            $table->string('education_level')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('employment_status')->nullable();
            $table->float('monthly_income', 10, 2)->nullable();

            // Child-specific (nullable, only when subtype = 'child')
            $table->date('date_of_father_death')->nullable();
            $table->string('school_year')->nullable();
            $table->unsignedTinyInteger('sibling_number')->nullable();
            $table->text('behavior_notes')->nullable();

            $table->timestamps();

            $table->index(['family_id', 'subtype']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_family_members');
        Schema::dropIfExists('beneficiary_families');
    }
};
