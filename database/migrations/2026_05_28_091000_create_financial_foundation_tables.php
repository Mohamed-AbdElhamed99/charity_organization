<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financial Foundation Tables
 *
 * accounts        — Org bank accounts that hold money
 * payment_methods — How money moves: cash, cheque, bank transfer, Zelle, Stripe etc.
 * currencies      — Currency reference for multi-currency support
 *
 * Every financial transaction in the system references an account.
 * The accounts table is the source of truth for the bank statement report (#8).
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Currencies ---
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('ISO 4217 code e.g. USD, EGP, EUR');
            $table->string('name')->comment('Full currency name e.g. US Dollar');
            $table->string('symbol', 10)->nullable()->comment('Symbol e.g. $, £, ج.م');
            $table->boolean('is_default')->default(false)->comment('The primary operating currency of the org');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // --- Bank / Cash Accounts ---
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Account display name e.g. "Chase Business Checking"');
            $table->string('account_number')->nullable()->comment('Bank account number');
            $table->string('bank_name')->nullable()->comment('Bank name e.g. Chase, Wells Fargo');
            $table->string('bank_branch')->nullable();

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete()
                ->comment('Operating currency of this account');

            $table->enum('type', ['bank', 'cash', 'digital'])
                ->default('bank')
                ->comment('bank: traditional bank account; cash: petty cash; digital: e.g. PayPal, Zelle wallet');

            $table->float('opening_balance', 14, 2)->default(0)
                ->comment('Starting balance when account was added to the system');

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        // --- Payment Methods ---
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g. Cash, Cheque, Bank Transfer, Zelle, Stripe, Credit Card');
            $table->string('code', 50)->unique()->comment('Machine-readable code e.g. cash, cheque, zelle, stripe');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('currencies');
    }
};
