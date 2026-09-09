<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSectionItem extends Model
{
    protected $fillable = [
        'section_id', 'icon', 'image', 'value_en', 'value_ms',
        'label_en', 'label_ms', 'description_en', 'description_ms',
        'sort_order', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function section() { return $this->belongsTo(WebsiteSection::class, 'section_id'); }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset($this->image) : null;
    }
}