<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds donor-selectable recurrence frequency (weekly/monthly/quarterly/yearly)
 * to recurring donations, and replaces the month-day-only `billing_anchor_day`
 * with a full `billing_cycle_anchor_at` timestamp (taken directly from
 * Stripe's `subscription.billing_cycle_anchor`) so anchoring works uniformly
 * across all frequencies, not just monthly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->string('frequency')->default('monthly')->after('donor_covers_fee')
                ->comment('weekly|monthly|quarterly|yearly');

            $table->timestamp('billing_cycle_anchor_at')->nullable()->after('billing_anchor_day')
                ->comment('Stripe subscription.billing_cycle_anchor as a timestamp; works for any frequency');
        });

        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_anchor_day');
        });
    }

    public function down(): void
    {
        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->unsignedTinyInteger('billing_anchor_day')->nullable()->after('stripe_subscription_id');
        });

        Schema::table('donation_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'billing_cycle_anchor_at']);
        });
    }
};
