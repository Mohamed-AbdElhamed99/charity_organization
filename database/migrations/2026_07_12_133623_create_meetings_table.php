<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('meeting_number')->unique();
            $table->string('type');
            $table->string('status')->default('scheduled');
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('location_type')->default('physical');
            $table->string('meeting_link')->nullable();
            $table->text('agenda')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('quorum_required')->nullable();
            $table->boolean('quorum_met')->default(false);
            $table->string('chairperson')->nullable();
            $table->string('secretary')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('meeting_date');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
