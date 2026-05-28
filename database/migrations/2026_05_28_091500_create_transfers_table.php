<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transfers — Money Sent to People or Vendors
 *
 * Records money transferred out of an org account to an external recipient.
 * This covers: paying a vendor/supplier for campaign goods, sending aid money
 * directly to a beneficiary, or wire transfers to partner organizations.
 *
 * Supports Report #3 — Transfer Money Report:
 *   (transaction_date, person_receives_money, purpose, amount_transferred, payment_method)
 *
 * Supports Report #8 — Total Transactions (via parent transactions table)
 *
 * recipient_type distinguishes between:
 *   - vendor:      A supplier being paid for goods/services for a campaign
 *   - beneficiary: Direct cash transfer to a beneficiary
 *   - other:       Any other external recipient
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            // Link to unified ledger
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('One-to-one: the transaction record for this transfer');

            // Campaign context (optional — transfers can be general)
            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete()
                ->comment('The campaign this transfer relates to; null = general org transfer');

            // Recipient details
            $table->enum('recipient_type', ['vendor', 'beneficiary', 'user', 'other'])
                ->comment('vendor: supplier; beneficiary: direct aid transfer; user: staff reimbursement; other: misc');

            $table->string('recipient_name')
                ->comment('Name of person or organization receiving the transfer');

            $table->string('recipient_phone')->nullable()
                ->comment('Contact phone of recipient');

            // For report #3 — "person receives money"
            // When recipient_type = beneficiary, link to beneficiary record
            $table->foreignId('beneficiary_id')
                ->nullable()
                ->constrained('beneficiaries')
                ->nullOnDelete()
                ->comment('Link to beneficiary record when recipient_type = beneficiary');

            // For vendor payments, link to user who is a vendor/supplier contact
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Link to user record when recipient_type = user (staff reimbursement)');

            // Transfer details
            $table->float('amount',   2)
                ->comment('Amount transferred');

            $table->date('transfer_date')
                ->comment('Date the transfer was made');

            $table->string('purpose')
                ->comment('What the transfer is for e.g. "Food boxes purchase", "Medical aid for case BEN-2026-0001"');

            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff who recorded this transfer');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for Transfer Money Report
            $table->index('transfer_date');
            $table->index('recipient_type');
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
