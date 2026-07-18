<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `User` now implements `MustVerifyEmail`, which activates the previously
 * no-op `verified` middleware on admin routes. Backfill existing accounts so
 * no current admin/staff user is locked out.
 *
 * `password_set_at` distinguishes donors who have set a real password from
 * guest-checkout-created accounts (random password via
 * DonorIdentityService::firstOrCreateByEmail), so registration can redirect
 * the latter into a "claim your account" password-reset flow instead of a
 * duplicate-email error. Existing rows are backfilled to now() since they
 * predate this feature and are assumed to already have usable passwords.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        DB::table('users')->update(['password_set_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
