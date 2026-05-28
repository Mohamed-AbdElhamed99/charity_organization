<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary Organizations Profile
 *
 * For partner organizations that receive support from New Egypt.
 * Examples: Kidney Center, elderly care homes, community centers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_organizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->unique()
                ->constrained('beneficiaries')
                ->cascadeOnDelete()
                ->comment('One-to-one with beneficiaries base table');

            // Identity
            $table->string('name')->comment('Organization name');
            $table->string('organization_type')->nullable()
                ->comment('e.g. Hospital, Charity, School, Care Home, Community Center');
            $table->string('charity_number')->nullable()
                ->comment('Official charity registration number if applicable');

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();

            // Additional info
            $table->string('contact_person')->nullable()
                ->comment('Name of the primary contact at the organization');
            $table->string('contact_phone')->nullable()
                ->comment('Phone of the primary contact person');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_organizations');
    }
};
