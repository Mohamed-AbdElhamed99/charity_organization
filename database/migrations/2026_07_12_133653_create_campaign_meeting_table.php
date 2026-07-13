<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_meeting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('relationship_type')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'meeting_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_meeting');
    }
};
