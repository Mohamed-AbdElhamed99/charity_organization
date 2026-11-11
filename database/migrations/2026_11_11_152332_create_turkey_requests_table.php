<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turkey requests — temporary table for the "Free Halal Turkey" campaign.
 *
 * Each public form submission creates one row (plus an individual beneficiary).
 * Pending and delivered rows count against the campaign stock; cancelled rows free it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turkey_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->restrictOnDelete()
                ->comment('Individual beneficiary created from the form submission');
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->restrictOnDelete()
                ->comment('Hidden internal campaign this request belongs to');
            $table->foreignId('beneficiary_support_id')
                ->nullable()
                ->constrained('beneficiary_supports')
                ->nullOnDelete()
                ->comment('Support record created once delivery is verified');

            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50);

            $table->string('code')->unique()
                ->comment('Verification code handed to the beneficiary at delivery');
            $table->enum('status', ['pending', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turkey_requests');
    }
};
