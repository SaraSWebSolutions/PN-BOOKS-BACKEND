<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    const TYPE_HELP_SUPPORT = 'help_support';
    const TYPE_FAQ          = 'faq';
    const TYPE_BOTH         = 'both';

    const TYPES = [
        self::TYPE_HELP_SUPPORT => 'Help & Support',
        self::TYPE_FAQ          => 'FAQ',
        self::TYPE_BOTH         => 'Both',
    ];

    protected $fillable = [
        'question', 'answer', 'type', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // FAQs for one page. Items marked "both" show on every page.
    public function scopeForType($query, string $type)
    {
        return $query->whereIn('type', [$type, self::TYPE_BOTH]);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}