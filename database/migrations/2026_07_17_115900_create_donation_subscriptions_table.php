<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donation Subscriptions
 *
 * The source of truth for a donor's active recurring (monthly) commitment.
 * Each successful billing cycle still produces its own row in `donations`
 * (linked back here via `donations.donation_subscription_id`), matching the
 * app's existing one-row-per-gift ledger model.
 *
 * Payment method storage/management and cancellation are delegated to
 * Stripe: donors are routed to Stripe's hosted Customer Portal rather than
 * a bespoke donor login inside this app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('donor_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The donor who committed to this recurring donation');

            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete()
                ->comment('Campaign this recurring donation is designated for; null = general fund');

            $table->boolean('is_general')->default(false)
                ->comment('True when this recurring donation is not tied to any specific campaign');

            $table->unsignedBigInteger('amount_cents')
                ->comment('Donor intended recurring gift amount in cents, charged each cycle');

            $table->boolean('donor_covers_fee')->default(false)
                ->comment('Whether the donor opted to cover the processing fee each cycle');

            $table->string('stripe_customer_id')
                ->comment('Stripe Customer ID billed for this subscription');

            $table->string('stripe_subscription_id')->unique()
                ->comment('Stripe Subscription ID for webhook reconciliation');

            $table->unsignedTinyInteger('billing_anchor_day')
                ->comment('Day of month (1-31) the subscription bills on, set from the first payment date');

            $table->string('status')->default('active')
                ->comment('active|past_due|canceled');

            $table->json('metadata')->nullable()
                ->comment('Extra context captured at subscription creation (e.g. anonymity, message)');

            $table->timestamps();
            $table->softDeletes();

            $table->index('donor_id');
            $table->index('campaign_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_subscriptions');
    }
};
