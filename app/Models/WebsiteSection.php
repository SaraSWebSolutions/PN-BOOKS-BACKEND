<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSection extends Model
{
    protected $fillable = [
        'page_id', 'section_key', 'icon', 'image',
        'label_en', 'label_ms', 'value_en', 'value_ms',
        'sort_order', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }
    public function items()
{
    return $this->hasMany(WebsiteSectionItem::class, 'section_id')->orderBy('sort_order');
}

    public function getImageUrlAttribute()
    {
        return $this->image ? asset($this->image) : null;
    }
}