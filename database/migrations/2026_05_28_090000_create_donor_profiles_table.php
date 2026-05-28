<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donor Profiles
 *
 * Donors are users with the 'donor' role (managed via Spatie).
 * This table holds donor-specific profile data that does not belong on the base users table.
 * One-to-one with users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The user account this donor profile belongs to');

            // Donor classification
            $table->enum('type', ['individual', 'organization'])
                ->default('individual')
                ->comment('Whether the donor is a private individual or an organization');

            $table->string('organization_name')->nullable()
                ->comment('Required when type = organization');

            // Contact & location (donor may differ from user address)
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();

            // Notes by admin
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for report queries: donor name/type lookups
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_profiles');
    }
};
