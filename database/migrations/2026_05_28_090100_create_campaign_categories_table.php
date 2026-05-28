<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign Categories
 *
 * Owner-defined categories to classify campaigns.
 * Examples: Medical, Housing, Seasonal, Community Event, Education, Emergency.
 * Bilingual (Arabic + English) to match org's operational language.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar')->comment('Category name in Arabic');
            $table->string('name_en')->comment('Category name in English');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_categories');
    }
};
