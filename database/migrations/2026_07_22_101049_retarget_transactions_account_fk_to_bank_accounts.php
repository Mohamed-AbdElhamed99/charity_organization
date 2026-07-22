<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * After renaming accounts → bank_accounts, retarget the transactions.account_id
 * foreign key so it explicitly references bank_accounts (and keeps nullOnDelete
 * from the legacy-import relaxation migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')
                ->on('bank_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();
        });
    }
};
