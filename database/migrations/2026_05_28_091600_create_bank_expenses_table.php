<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank Expenses
 *
 * Represents direct bank account debit entries that don't fall under
 * campaign_expenses, general_expenses, or transfers.
 * Examples: bank fees, wire transfer charges, returned cheques, adjustments.
 *
 * Supports Report #2 — Bank Expenses Report:
 *   (date, description, account_name, account_number, amount)
 *
 * Note: The account_name and account_number for the report are
 * joined from the accounts table via transaction → account.
 * This table just holds the bank-specific detail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_expenses', function (Blueprint $table) {
            $table->id();

            // Link to unified ledger
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('One-to-one: the transaction record for this bank expense');

            $table->string('description')
                ->comment('Bank expense description e.g. "Monthly service fee", "Wire transfer charge"');

            $table->float('amount',   2)
                ->comment('Amount of the bank expense');

            $table->date('expense_date')
                ->comment('Date this appeared on the bank statement');

            // Supporting document (stored via Spatie Media Library)
            // Use media morphs on this model — no file column needed here

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_expenses');
    }
};
