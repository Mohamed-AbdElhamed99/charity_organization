<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turkey_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1)->after('address')
                ->comment('Number of turkeys reserved for this person');
        });
    }

    public function down(): void
    {
        Schema::table('turkey_requests', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
