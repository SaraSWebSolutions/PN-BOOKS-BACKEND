<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'value_en', 'value_ms', 'type'];

    /** WebsiteSetting::get('site_name', 'ms') */
    public static function get(string $key, string $locale = 'en', $default = null)
    {
        $row = static::where('key', $key)->first();
        if (! $row) return $default;
        return $locale === 'ms' ? ($row->value_ms ?: $row->value_en) : $row->value_en;
    }
}