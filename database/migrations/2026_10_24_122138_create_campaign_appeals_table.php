<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign appeals — shareable, unit-priced gifts under a campaign.
 *
 * A campaign can publish several appeals (a food box, a blanket) that each
 * have their own link, photo, and price. Money is still credited to the
 * parent campaign.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_appeals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete()
                ->comment('Campaign this gift is credited to');

            $table->string('slug')->unique()->comment('Public share path /give/{slug}');
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('subtitle_en')->nullable();
            $table->text('subtitle_ar')->nullable();

            $table->unsignedInteger('unit_amount_cents')
                ->comment('Price of one unit in cents, e.g. 1500 = $15');
            $table->string('unit_label_en')->comment('Singular noun, e.g. box');
            $table->string('unit_label_ar');
            $table->string('unit_label_plural_en')->comment('Plural noun, e.g. boxes');
            $table->string('unit_label_plural_ar');

            $table->json('quantity_options')
                ->comment('Preset quantities shown as choices, e.g. [1, 3, 5]');
            $table->boolean('allow_custom_amount')->default(true)
                ->comment('Whether the donor may enter a dollar amount instead of a quantity');

            $table->string('cta_label_en');
            $table->string('cta_label_ar');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['campaign_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_appeals');
    }
};
