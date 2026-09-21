<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'user_id', 'rating', 'title', 'review',
        'is_verified_purchase', 'is_approved', 'helpful_count',
    ];

    protected $casts = [
        'rating'                => 'integer',
        'is_verified_purchase'  => 'boolean',
        'is_approved'           => 'boolean',
        'helpful_count'         => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}