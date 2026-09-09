<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookSeo extends Model
{
    protected $table = 'book_seo';

    protected $fillable = [
        'book_id', 'seo_title', 'meta_description', 'url_slug',
        'meta_keywords', 'canonical_url',
        'allow_index', 'allow_follow', 'enable_structured_data', 'robots_setting',
    ];

    protected $casts = [
        'meta_keywords'           => 'array',
        'allow_index'             => 'boolean',
        'allow_follow'            => 'boolean',
        'enable_structured_data'  => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}