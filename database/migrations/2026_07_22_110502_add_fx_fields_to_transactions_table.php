<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('original_currency_id')
                ->nullable()
                ->after('currency_id')
                ->constrained('currencies')
                ->nullOnDelete();

            $table->decimal('original_amount', 14, 2)
                ->nullable()
                ->after('net_amount');

            $table->decimal('exchange_rate', 18, 8)
                ->nullable()
                ->after('original_amount')
                ->comment('Rate from original currency to account currency');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('original_currency_id');
            $table->dropColumn(['original_amount', 'exchange_rate']);
        });
    }
};
