<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relaxes the NOT NULL foreign-key constraints on the unified ledger and its
 * detail tables so that messy legacy data can be imported with NULLs.
 *
 * Going forward, presence of these fields on NEW records is enforced at the
 * application layer (FormRequest validation), not the database. The FKs are
 * kept (so referential integrity still holds when a value IS present) but
 * switched to nullOnDelete to stay consistent with their now-nullable state.
 *
 * Columns already nullable in the base schema (donations.donor_id,
 * donations.campaign_id, general_expenses.category_id, transactions.payment_method_id)
 * are intentionally left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->change();
            $table->unsignedBigInteger('currency_id')->nullable()->change();
            $table->unsignedBigInteger('created_by')->nullable()->change();

            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['responsible_user_id']);
        });
        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->change();
            $table->unsignedBigInteger('item_id')->nullable()->change();
            $table->unsignedBigInteger('responsible_user_id')->nullable()->change();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->nullOnDelete();
            $table->foreign('responsible_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('general_expenses', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverts to NOT NULL + RESTRICT. This will FAIL if any imported row still
     * holds a NULL in these columns — clean or backfill the data first.
     */
    public function down(): void
    {
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['item_id']);
            $table->dropForeign(['responsible_user_id']);
        });
        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable(false)->change();
            $table->unsignedBigInteger('item_id')->nullable(false)->change();
            $table->unsignedBigInteger('responsible_user_id')->nullable(false)->change();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('responsible_user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
            $table->unsignedBigInteger('currency_id')->nullable(false)->change();
            $table->unsignedBigInteger('created_by')->nullable(false)->change();

            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
        });
    }
};