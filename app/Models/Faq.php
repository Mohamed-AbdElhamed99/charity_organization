<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->orderBy('sort_order');
    }

    protected function question(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->question_ar ?? $this->question_en)
                : ($this->question_en ?? $this->question_ar),
        );
    }

    protected function answer(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->answer_ar ?? $this->answer_en)
                : ($this->answer_en ?? $this->answer_ar),
        );
    }

    protected $appends = ['question', 'answer'];
}
