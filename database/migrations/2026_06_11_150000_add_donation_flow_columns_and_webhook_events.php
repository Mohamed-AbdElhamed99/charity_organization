<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donation flow schema deltas (additive).
 *
 * Enables pending donations before ledger write, donor-intent amount (D) on donations,
 * campaign collected totals in cents, and Stripe webhook idempotency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            // Pending Stripe donations exist before a ledger row is created in the webhook.
            $table->unsignedBigInteger('transaction_id')->nullable()->change();

            // Donor's intended gift (D) in cents — receipts and campaign progress use this.
            $table->unsignedBigInteger('amount')->nullable()
                ->comment('Donor intended gift in cents (D); campaign credited by this amount');

            // Lifecycle status for online donations (stripe_status kept for Stripe mirror).
            $table->string('status')->default('pending')
                ->comment('pending|succeeded|failed|refunded|requires_action');

            $table->boolean('is_anonymous')->default(false)
                ->comment('Hide donor identity on public surfaces; admins still see real identity');

            $table->timestamp('receipt_sent_at')->nullable()
                ->comment('When the donation receipt email was successfully sent');

            $table->text('donor_message')->nullable()
                ->comment('Optional message from the donor at checkout');

            $table->json('metadata')->nullable()
                ->comment('Extra checkout context (e.g. fee estimates, locale)');

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();

            $table->index('stripe_charge_id');
            $table->index('status');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            // Running total of succeeded donation gifts (D) in cents for this campaign.
            $table->unsignedBigInteger('collected_amount')->default(0)
                ->comment('Sum of donations.amount (cents) credited on successful webhook processing');
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique()
                ->comment('Stripe event ID for idempotent webhook handling');
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('collected_amount');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropIndex(['stripe_charge_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'amount',
                'status',
                'is_anonymous',
                'receipt_sent_at',
                'donor_message',
                'metadata',
            ]);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable(false)->change();
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->cascadeOnDelete();
        });
    }
};
