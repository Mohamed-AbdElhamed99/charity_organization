<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donations — Income Detail Table
 *
 * Extends the transactions table for donation-specific data.
 * Every donation record has exactly one transaction record (direction: in).
 *
 * A donation can be:
 *   - General (no campaign): donor gives to the org broadly
 *   - Campaign-specific: donor designates funds for a specific campaign
 *
 * Supports Report #1  — Donation Report (date, donor, type, purpose, gross, fee, amount)
 * Supports Report #6  — Fees Report (date, donor, type, fee, purpose)
 * Supports Report #8  — Total Transactions (via parent transactions table)
 *
 * Stripe integration note: stripe_payment_intent_id stored here for webhook
 * reconciliation. gross/fee/net on the parent transactions table cover the
 * financial split. This is Phase 1 — Stripe will be wired up in Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // Link to unified ledger
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('One-to-one: the transaction record for this donation');

            // Who donated
            $table->foreignId('donor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('The donor user; nullable for anonymous donations');

            // Purpose / designation
            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete()
                ->comment('Campaign this donation is designated for; null = general donation to org');

            $table->boolean('is_general')
                ->default(false)
                ->comment('True when donation is not tied to any specific campaign');

            $table->string('purpose_note')->nullable()
                ->comment('Free-text purpose from donor e.g. "For the orphan children project"');

            // Stripe (Phase 2 — stored now, wired later)
            $table->string('stripe_payment_intent_id')->nullable()->unique()
                ->comment('Stripe PaymentIntent ID for webhook reconciliation');
            $table->string('stripe_charge_id')->nullable()
                ->comment('Stripe Charge ID after payment confirmation');
            $table->enum('stripe_status', ['pending', 'succeeded', 'failed', 'refunded'])
                ->nullable()
                ->comment('Stripe payment status; null for non-Stripe donations');

            $table->boolean('donor_covers_fee')->default(false)
                ->comment('Whether the donor opted to cover the processing fee');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for Donation Report and Fees Report
            $table->index('donor_id');
            $table->index('campaign_id');
            $table->index('is_general');
            $table->index('stripe_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
