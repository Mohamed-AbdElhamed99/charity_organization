<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The single campaign/is_general target on `donation_subscriptions` is
 * superseded by `donation_subscription_allocations` (backfilled in the
 * previous migration), which supports splitting one recurring donation
 * across multiple campaigns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropIndex(['campaign_id']);
            $table->dropColumn(['campaign_id', 'is_general']);
        });
    }

    public function down(): void
    {
        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('donor_id')
                ->constrained('campaigns')
                ->nullOnDelete()
                ->comment('Campaign this recurring donation is designated for; null = general fund');

            $table->boolean('is_general')->default(false)->after('campaign_id')
                ->comment('True when this recurring donation is not tied to any specific campaign');

            $table->index('campaign_id');
        });
    }
};
