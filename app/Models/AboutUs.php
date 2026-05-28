<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Single-record model — only one row should exist.
 * Use AboutUs::first() or AboutUs::sole() to retrieve.
 */
class AboutUs extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'about_us';

    protected $guarded = ['id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('team_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
