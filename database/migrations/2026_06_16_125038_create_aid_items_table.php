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
        Schema::create('aid_items', function (Blueprint $table) {
            $table->id();
            $table->json('name')
                ->comment('Translatable item name payload e.g. {"en":"Food Box","ar":"كرتونة غذاء"}');
            $table->json('unit')
                ->nullable()
                ->comment('Translatable unit payload e.g. {"en":"box","ar":"صندوق"}');
            $table->unsignedBigInteger('default_unit_cost')
                ->nullable()
                ->comment('Default unit cost snapshot in minor units (cents)');
            $table->string('category')
                ->nullable()
                ->comment('Optional aid item category for filtering and grouping');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Whether this catalog item can be selected in support entry forms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_items');
    }
};
