<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items & Campaign Expenses
 *
 * items             — Reusable catalog of purchasable items e.g. Food Box, Medicine, Clothing
 * campaign_expenses — Line-item expenses incurred for a specific campaign
 *
 * Supports Report #4 — Expenses Report:
 *   (expense_date, responsible_user, item, item_price, quantity, amount, residual_qty, residual_amount)
 *
 * residual_quantity / residual_amount track leftover goods after distribution.
 * Example: bought 100 food boxes (quantity=100), distributed 80 (residual_quantity=20).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Items catalog ---
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->comment('Item name in Arabic');
            $table->string('name_en')->comment('Item name in English');
            $table->text('description')->nullable();
            $table->string('unit')->nullable()->comment('Unit of measure e.g. Box, Piece, Kg, Litre');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // --- Campaign expenses ---
        Schema::create('campaign_expenses', function (Blueprint $table) {
            $table->id();

            // Link to unified ledger
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->comment('One-to-one: the transaction record for this expense');

            // Campaign this expense belongs to
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->restrictOnDelete()
                ->comment('The campaign this expense was incurred for');

            // What was purchased
            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete()
                ->comment('The item purchased');

            $table->float('item_price', 10, 2)
                ->comment('Unit price at time of purchase');

            $table->float('quantity', 10, 3)
                ->comment('Quantity purchased; decimal supports fractional units e.g. 2.5 kg');

            $table->float('amount',   2)
                ->comment('Total amount = item_price * quantity');

            // Residual tracking (goods not yet distributed)
            $table->float('residual_quantity', 10, 3)->default(0)
                ->comment('Quantity of item remaining after distribution; starts = quantity');

            $table->float('residual_amount',   2)->default(0)
                ->comment('Value of remaining goods = residual_quantity * item_price');

            // Responsibility
            $table->foreignId('responsible_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff member responsible for this purchase');

            $table->date('expense_date')
                ->comment('Date of purchase/expense');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for Expenses Report queries
            $table->index('campaign_id');
            $table->index('expense_date');
            $table->index(['campaign_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_expenses');
        Schema::dropIfExists('items');
    }
};
