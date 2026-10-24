<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the appeal a donation was made through.
 *
 * quantity is null for a custom dollar amount. unit_amount_cents and
 * unit_label freeze the price and noun at the time of the gift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->foreignId('appeal_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('campaign_appeals')
                ->nullOnDelete();
            $table->unsignedInteger('quantity')
                ->nullable()
                ->after('appeal_id');
            $table->unsignedInteger('unit_amount_cents')
                ->nullable()
                ->after('quantity');
            $table->string('unit_label')
                ->nullable()
                ->after('unit_amount_cents');

            $table->index('appeal_id');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appeal_id');
            $table->dropColumn(['quantity', 'unit_amount_cents', 'unit_label']);
        });
    }
};
