<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS extension tables:
 * legal_documents — singleton legal pages (terms, privacy) keyed by type
 * faqs            — ordered, publishable FAQ entries
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->longText('body_ar');
            $table->longText('body_en')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('question_ar');
            $table->text('question_en')->nullable();
            $table->longText('answer_ar');
            $table->longText('answer_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sort_order');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('legal_documents');
    }
};
