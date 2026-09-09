<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookReview extends Model
{
    protected $fillable = [
        'book_id', 'user_id', 'reviewer_name', 'rating',
        'title', 'comment', 'is_approved', 'helpful_count',
    ];

    protected $casts = ['is_approved' => 'boolean'];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function scopeApproved($q) { return $q->where('is_approved', true); }
}