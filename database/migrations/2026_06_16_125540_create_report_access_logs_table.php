<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Actor who accessed beneficiary report data');
            $table->string('report_key')
                ->comment('Machine-readable report key e.g. campaign_beneficiary_report');
            $table->string('scope_type')
                ->comment('Scope model type e.g. campaign or beneficiary');
            $table->unsignedBigInteger('scope_id')
                ->comment('Scoped model identifier for the report access');
            $table->string('action')
                ->comment('Action taken e.g. view or export');
            $table->unsignedInteger('row_count')
                ->default(0)
                ->comment('Number of rows shown/exported for this access event');
            $table->json('filters')
                ->nullable()
                ->comment('Filter snapshot at time of report access');
            $table->timestamps();

            $table->index(['report_key', 'scope_type', 'scope_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_access_logs');
    }
};
