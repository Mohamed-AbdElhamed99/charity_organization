<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS Tables
 *
 * news_categories  — Categories for news articles
 * news             — News & articles (merged: old schema had separate articles table but
 *                    they were identical — kept as one 'news' entity)
 * about_us         — Single-record org about page content
 * sliders          — Homepage hero video sliders
 * contact_us       — Public contact form submissions
 *
 * All content tables are bilingual (Arabic + English).
 * Media (images, videos) are handled via Spatie Media Library (media table with morphs).
 * No separate _images tables needed — media morph handles this cleanly.
 *
 * Writer/author is the authenticated user (created_by FK) — no separate authors table needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- News Categories ---
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // --- News (merged news + articles) ---
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('news_categories')
                ->nullOnDelete();

            // Identity
            $table->string('slug')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('subtitle_ar')->nullable();
            $table->string('subtitle_en')->nullable();

            // Content
            $table->text('excerpt_ar')->nullable()->comment('Short teaser shown in listing cards');
            $table->text('excerpt_en')->nullable();
            $table->longText('body_ar')->nullable()->comment('Full article body in Arabic');
            $table->longText('body_en')->nullable()->comment('Full article body in English');

            // Media — images and cover stored via Spatie Media Library (morphs)
            // video embed link stored here directly (YouTube/Vimeo embed URL)
            $table->string('video_url')->nullable()->comment('Embed URL for video content');

            // Publication
            $table->date('published_at')->nullable()->comment('Publication date; null = not yet published');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_private')->default(false)
                ->comment('false = public website; true = members only');

            // SEO
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();

            // Authorship — the user who wrote/posted this
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('User who created/authored this news article');

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('published_at');
            $table->index('category_id');
        });

        // --- About Us (single record — org profile page) ---
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();

            $table->text('mission_title_ar')->nullable();
            $table->text('mission_title_en')->nullable();
            $table->longText('mission_description_ar')->nullable();
            $table->longText('mission_description_en')->nullable();

            $table->text('message_title_ar')->nullable();
            $table->text('message_title_en')->nullable();
            $table->longText('message_description_ar')->nullable();
            $table->longText('message_description_en')->nullable();

            $table->text('team_title_ar')->nullable();
            $table->text('team_title_en')->nullable();
            $table->longText('team_description_ar')->nullable();
            $table->longText('team_description_en')->nullable();

            $table->longText('body_ar')->nullable()->comment('Free-form additional about content');
            $table->longText('body_en')->nullable();

            $table->string('video_url')->nullable()->comment('Org intro video embed URL');

            $table->timestamps();
            $table->softDeletes();
        });

        // --- Sliders (homepage hero banners) ---
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();

            // Videos stored via Spatie Media Library with collection names:
            // 'video_desktop_ar', 'video_mobile_ar', 'video_desktop_en', 'video_mobile_en'
            // Keeping direct URL columns for simple embed scenarios
            $table->string('video_desktop_ar')->nullable();
            $table->string('video_mobile_ar')->nullable();
            $table->string('video_desktop_en')->nullable();
            $table->string('video_mobile_en')->nullable();

            $table->unsignedSmallInteger('order')->default(0)
                ->comment('Display order; lower = shown first');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        // --- Contact Us ---
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();

            $table->string('fullname');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');

            $table->boolean('is_reviewed')->default(false)
                ->comment('Whether a staff member has reviewed this submission');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Staff who marked this as reviewed');

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable()
                ->comment('Internal notes from the reviewer');

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_reviewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_us');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('about_us');
        Schema::dropIfExists('news');
        Schema::dropIfExists('news_categories');
    }
};
