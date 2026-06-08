<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->title_ar ?? $this->title_en)
                : ($this->title_en ?? $this->title_ar),
        );
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->body_ar ?? $this->body_en)
                : ($this->body_en ?? $this->body_ar),
        );
    }

    protected $appends = ['title', 'body'];
}
