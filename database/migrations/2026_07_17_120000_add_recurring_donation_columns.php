<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring donations schema deltas (additive).
 *
 * Adds a Stripe Customer reference to donor profiles (needed so a donor's
 * saved payment method can be reused for monthly billing and so donors can
 * be routed to Stripe's hosted Customer Portal), and tags individual
 * donation rows produced by a subscription's recurring invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_profiles', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->unique()->after('user_id')
                ->comment('Stripe Customer ID; created the first time this donor sets up a recurring donation');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('donor_covers_fee')
                ->comment('True when this donation was produced by a recurring subscription invoice');

            $table->foreignId('donation_subscription_id')->nullable()->after('campaign_id')
                ->constrained('donation_subscriptions')
                ->nullOnDelete()
                ->comment('The recurring subscription this donation belongs to, if any');

            $table->string('stripe_invoice_id')->nullable()->unique()->after('stripe_payment_intent_id')
                ->comment('Stripe Invoice ID for recurring donations; used for webhook idempotency');

            $table->index('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donation_subscription_id']);
            $table->dropIndex(['is_recurring']);
            $table->dropColumn(['is_recurring', 'donation_subscription_id', 'stripe_invoice_id']);
        });

        Schema::table('donor_profiles', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
