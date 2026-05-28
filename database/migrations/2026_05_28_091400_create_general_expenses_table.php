<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * General Expenses — Org Operational Costs
 *
 * Expenses NOT related to any specific campaign.
 * These are org running costs: Zoom, Gusto, Aplos, Google Workspace, Zoho etc.
 *
 * Supports Report #7 — New Egypt General Expenses (name, amount)
 * Supports Report #8 — Total Transactions (via parent transactions table)
 *
 * general_expense_categories provides classification for grouping in reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- General expense categories ---
        Schema::create('general_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g. Software & Subscriptions, Office, Utilities, Salaries, Marketing');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // --- General expenses ---
        Schema::create('general_expenses', function (Blueprint $table) {
            $table->id();

            // Link to unified ledger
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('One-to-one: the transaction record for this general expense');

            // Classification
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('general_expense_categories')
                ->nullOnDelete()
                ->comment('Category for grouping in Report #7');

            // Expense details
            $table->string('name')
                ->comment('Expense name e.g. "Zoom Pro Monthly", "Google Workspace", "Office Rent"');

            $table->float('amount',   2)
                ->comment('Total amount of this expense');

            $table->date('expense_date')
                ->comment('Date this expense was incurred');

            // Vendor / payee
            $table->string('vendor_name')->nullable()
                ->comment('Who was paid e.g. Zoom, Google, Landlord name');

            $table->boolean('is_recurring')->default(false)
                ->comment('Whether this is a recurring monthly/annual subscription');

            // Responsibility
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff who recorded this expense');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for Report #7
            $table->index('category_id');
            $table->index('expense_date');
            $table->index('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_expenses');
        Schema::dropIfExists('general_expense_categories');
    }
};
