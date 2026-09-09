<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookFile extends Model
{
    protected $fillable = [
        'book_id', 'book_format_id', 'file_type',
        'file_name', 'file_path', 'file_size', 'status', 'sort_order',
    ];

    protected $appends = ['file_url'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function format()
    {
        return $this->belongsTo(BookFormat::class, 'book_format_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset($this->file_path) : null;
    }
}