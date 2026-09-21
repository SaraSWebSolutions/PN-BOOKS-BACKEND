<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteBanner extends Model
{
    protected $fillable = [
        'page_id', 'title_en', 'title_ms', 'subtitle_en', 'subtitle_ms',
        'description_en', 'description_ms', 'image', 'mobile_image',
        'button_text_en', 'button_text_ms', 'button_url',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }

    // ✅ same idea as $book->cover_image_url in your Book model
  public function getImageUrlAttribute()
{
    return $this->image
        ? route('website.image', ['folder' => 'banners', 'filename' => basename($this->image)])
        : null;
}

public function getMobileImageUrlAttribute()
{
    return $this->mobile_image
        ? route('website.image', ['folder' => 'banners', 'filename' => basename($this->mobile_image)])
        : null;
}
}