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
        Schema::create('beneficiary_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')
                ->constrained('beneficiaries')
                ->restrictOnDelete()
                ->comment('Beneficiary receiving support in this distribution event');
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->restrictOnDelete()
                ->comment('Campaign under which this support event is recorded');
            $table->date('supported_at')
                ->comment('Operational date when support was delivered/planned/cancelled');
            $table->enum('status', ['planned', 'delivered', 'cancelled'])
                ->default('delivered')
                ->comment('Operational support lifecycle state; never posts to financial ledger');
            $table->text('notes')
                ->nullable()
                ->comment('Internal context about this support event');
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Staff user who recorded this support event');
            $table->timestamps();

            $table->index('beneficiary_id');
            $table->index('campaign_id');
            $table->index('supported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_supports');
    }
};
