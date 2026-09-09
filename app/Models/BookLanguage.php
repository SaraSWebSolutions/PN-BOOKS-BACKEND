<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookLanguage extends Model
{
    use HasFactory;

    protected $table = 'book_languages';

    protected $fillable = [
        'book_id',
        'language_id',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}