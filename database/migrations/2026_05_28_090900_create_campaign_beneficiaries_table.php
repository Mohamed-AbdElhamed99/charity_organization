<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign Beneficiaries (Pivot)
 *
 * Links campaigns to the beneficiaries who received aid in that campaign.
 * Many-to-many: one campaign can support multiple beneficiaries,
 * and one beneficiary can be supported across multiple campaigns.
 *
 * The aid_amount here records how much aid this specific beneficiary
 * received within this specific campaign — critical for the beneficiary
 * report showing total aid per person/family over time.
 *
 * aid_type clarifies whether the aid was financial, in-kind, or both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete();

            $table->float('aid_amount', 10, 2)
                ->default(0)
                ->comment('Monetary value of aid delivered to this beneficiary in this campaign');

            $table->enum('aid_type', ['financial', 'in_kind', 'both'])
                ->default('financial')
                ->comment('Type of aid delivered: financial cash, in-kind goods, or both');

            $table->text('aid_description')
                ->nullable()
                ->comment('Description of what aid was provided e.g. food boxes, medicine, renovation materials');

            $table->date('aid_date')
                ->nullable()
                ->comment('Date the aid was actually delivered to the beneficiary');

            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['campaign_id', 'beneficiary_id'], 'campaign_beneficiary_unique');

            // Index for beneficiary-centric report: all campaigns a beneficiary appeared in
            $table->index(['beneficiary_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_beneficiaries');
    }
};
