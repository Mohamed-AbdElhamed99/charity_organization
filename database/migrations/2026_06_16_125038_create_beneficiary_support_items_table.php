<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beneficiary_support_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_support_id')
                ->constrained('beneficiary_supports')
                ->cascadeOnDelete()
                ->comment('Parent support distribution event for this line item');
            $table->foreignId('aid_item_id')
                ->nullable()
                ->constrained('aid_items')
                ->nullOnDelete()
                ->comment('Optional catalog item reference; nullable for ad-hoc support lines');
            $table->string('item_name_snapshot')
                ->comment('Immutable item label captured at entry time for historical reporting');
            $table->unsignedInteger('quantity')
                ->default(1)
                ->comment('Number of units distributed for this line item');
            $table->unsignedBigInteger('unit_cost')
                ->comment('Per-unit cost snapshot in minor units (cents)');
            $table->unsignedBigInteger('total_cost')
                ->comment('Line total snapshot in cents; expected quantity x unit_cost');
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete()
                ->comment('Currency used for this cost snapshot, typically system default');
            $table->foreignId('campaign_expense_id')
                ->nullable()
                ->constrained('campaign_expenses')
                ->nullOnDelete()
                ->comment('Optional linked campaign expense row for reconciliation only');
            $table->timestamps();

            $table->index('aid_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_support_items');
    }
};
