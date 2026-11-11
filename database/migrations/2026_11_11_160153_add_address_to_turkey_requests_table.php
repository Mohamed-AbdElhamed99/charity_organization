<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turkey_requests', function (Blueprint $table) {
            $table->string('address')->nullable()->after('phone')
                ->comment('Delivery address entered on the public form');
        });
    }

    public function down(): void
    {
        Schema::table('turkey_requests', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
