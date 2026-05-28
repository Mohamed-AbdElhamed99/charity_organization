<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beneficiary User Access Control
 *
 * Two-layer access model:
 *   Layer 1 (Spatie): User's role must have 'view_beneficiary_details' permission.
 *   Layer 2 (this table): Even with that permission, access to a specific
 *                         beneficiary must be explicitly granted here,
 *                         with exact fields the user may see.
 *
 * Field-level access:
 *   allowed_fields stores a JSON array of field names the user may view.
 *   Example: ["first_name", "address", "health_status"]
 *   The application checks this array before returning each field in responses.
 *
 * Expiry:
 *   expires_in_seconds stores raw seconds. null = permanent.
 *   Application converts to human-readable (days/weeks/months) for display.
 *   Access is considered expired when:
 *     granted_at + expires_in_seconds < now()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_user_access', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->cascadeOnDelete()
                ->comment('The beneficiary this access grant applies to');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The user being granted access');

            $table->foreignId('granted_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Super admin who created this grant');

            $table->json('allowed_fields')
                ->comment('Array of field names this user may view for this beneficiary. e.g. ["first_name","address","health_status"]');

            $table->unsignedBigInteger('expires_in_seconds')
                ->nullable()
                ->comment('Access duration in seconds from granted_at. null = permanent. App converts to days/weeks/months for display.');

            $table->timestamp('granted_at')
                ->comment('When the access grant was created; used as base for expiry calculation');

            $table->text('grant_reason')
                ->nullable()
                ->comment('Optional reason for granting access e.g. "Social worker assigned to this case"');

            $table->timestamps();

            // Ensure one grant record per user per beneficiary
            $table->unique(['beneficiary_id', 'user_id'], 'beneficiary_user_access_unique');

            // Indexes for access check queries (called on every beneficiary detail request)
            $table->index(['user_id', 'beneficiary_id']);
            $table->index('granted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_user_access');
    }
};
