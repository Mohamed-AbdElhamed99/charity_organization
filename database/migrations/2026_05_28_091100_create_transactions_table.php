<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactions — Unified Financial Ledger
 *
 * The single source of truth for ALL money movements in the org.
 * Every deposit and withdrawal is recorded here regardless of its nature.
 * This is what powers the Total Transactions / Bank Statement report (#8).
 *
 * transaction_type classifies the movement:
 *   - donation         → money in from a donor (links to donations table)
 *   - campaign_expense → money out for a campaign (links to campaign_expenses)
 *   - general_expense  → money out for org operations (links to general_expenses)
 *   - transfer         → money sent to a person/vendor (links to transfers)
 *   - bank_transfer    → money moved between org accounts (internal)
 *   - adjustment       → manual correction entry
 *
 * The detail tables (donations, campaign_expenses, general_expenses, transfers)
 * extend this record with their specific fields. This avoids the old schema's
 * problem of 6+ parallel disconnected financial tables.
 *
 * running_balance is computed and stored for the bank statement report to avoid
 * expensive SUM() window functions on every report load.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Which account this transaction hits
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete()
                ->comment('The org bank/cash account this transaction belongs to');

            // Transaction classification
            $table->enum('transaction_type', [
                'donation',
                'campaign_expense',
                'general_expense',
                'transfer',
                'bank_transfer',
                'adjustment',
            ])->comment('The nature of this financial movement; determines which detail table to join');

            $table->enum('direction', ['in', 'out'])
                ->comment('in = deposit / income; out = withdrawal / expense');

            // Amounts
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->float('gross_amount', 14, 2)
                ->comment('Amount before any fees e.g. Stripe gross');

            $table->float('fee_amount', 14, 2)->default(0)
                ->comment('Processing fee e.g. Stripe fee; 0 for non-payment-gateway transactions');

            $table->float('net_amount', 14, 2)
                ->comment('Amount actually received/spent = gross_amount - fee_amount');

            // Running balance (updated on each insert for performance)
            $table->decimal('running_balance', 14, 2)->nullable()
                ->comment('Account balance after this transaction; powers bank statement report');

            // Transaction details
            $table->date('transaction_date')
                ->comment('Date the transaction occurred (may differ from created_at)');

            $table->string('reference_number')->nullable()
                ->comment('Cheque number, wire reference, Stripe charge ID etc.');

            $table->string('description')
                ->comment('Human-readable description for bank statement display');

            $table->text('notes')->nullable()
                ->comment('Internal notes');

            // Payment method used
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->nullOnDelete();

            // Who recorded this transaction
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff user who entered this transaction');

            // Soft state
            $table->boolean('is_reconciled')->default(false)
                ->comment('Whether this transaction has been reconciled against bank statement');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for report queries
            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index('direction');
            $table->index(['account_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
