<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single paid invoice can now produce multiple `Donation` rows (one per
 * allocation when a recurring donation is split across campaigns), so
 * `stripe_invoice_id` can no longer be unique. Idempotency for webhook
 * retries is enforced at the application level in DonationWebhookService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropUnique(['stripe_invoice_id']);
            $table->index('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['stripe_invoice_id']);
            $table->unique('stripe_invoice_id');
        });
    }
};
