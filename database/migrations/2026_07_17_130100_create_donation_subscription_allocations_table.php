<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Donation Subscription Allocations
 *
 * The authoritative breakdown of a single recurring donation's per-cycle
 * charge across one or more campaigns (or the general fund). A
 * single-campaign subscription is simply one allocation row.
 * `donation_subscriptions.amount_cents` remains a denormalized total that
 * must equal the sum of its allocations' `amount_cents`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_subscription_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('donation_subscription_id')
                ->constrained('donation_subscriptions', indexName: 'dsa_subscription_id_foreign')
                ->cascadeOnDelete()
                ->comment('The recurring donation this allocation belongs to');

            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns', indexName: 'dsa_campaign_id_foreign')
                ->nullOnDelete()
                ->comment('Campaign this share is designated for; null = general fund');

            $table->boolean('is_general')->default(false)
                ->comment('True when this share is not tied to any specific campaign');

            $table->unsignedBigInteger('amount_cents')
                ->comment('This allocation\'s share of the subscription\'s per-cycle charge, in cents');

            $table->timestamps();

            $table->index('donation_subscription_id', 'dsa_subscription_id_index');
            $table->index('campaign_id', 'dsa_campaign_id_index');
        });

        // Backfill: every existing subscription had exactly one target
        // (its own campaign_id/is_general/amount_cents columns), so each
        // becomes a single allocation row equal to 100% of the subscription.
        if (Schema::hasColumn('donation_subscriptions', 'campaign_id')) {
            DB::table('donation_subscriptions')->orderBy('id')->each(function ($subscription) {
                DB::table('donation_subscription_allocations')->insert([
                    'donation_subscription_id' => $subscription->id,
                    'campaign_id' => $subscription->campaign_id,
                    'is_general' => $subscription->is_general,
                    'amount_cents' => $subscription->amount_cents,
                    'created_at' => $subscription->created_at,
                    'updated_at' => $subscription->updated_at,
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_subscription_allocations');
    }
};