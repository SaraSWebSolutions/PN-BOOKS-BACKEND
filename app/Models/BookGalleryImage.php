<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookGalleryImage extends Model
{
    protected $fillable = ['book_id', 'image_path', 'alt_text', 'sort_order'];
    protected $appends = ['image_url'];

    public function book() { return $this->belongsTo(Book::class); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}