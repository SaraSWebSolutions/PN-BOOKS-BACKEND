<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    protected $fillable = ['page_key', 'name_en', 'name_ms', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function banners()
    {
        return $this->hasMany(WebsiteBanner::class, 'page_id')->orderBy('sort_order');
    }

    public function sections()
    {
        return $this->hasMany(WebsiteSection::class, 'page_id')->orderBy('sort_order');
    }
}