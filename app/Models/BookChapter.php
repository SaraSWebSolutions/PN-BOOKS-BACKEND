<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookChapter extends Model
{
    protected $fillable = [
        'book_id', 'chapter_number', 'title', 'duration_seconds',
        'audio_file_path', 'file_size', 'status', 'sort_order',
    ];

    protected $appends = ['audio_url', 'duration_formatted'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_file_path ? asset($this->audio_file_path) : null;
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
    }
}