<?php

namespace Database\Seeders;

use App\Models\BookFormat;
use Illuminate\Database\Seeder;

class BookFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            ['code' => 'EBOOK',    'name' => 'eBook',        'icon' => 'feather-tablet',      'requires_shipping' => false],
            ['code' => 'AUDIOBOOK','name' => 'Audiobook',    'icon' => 'feather-headphones',  'requires_shipping' => false],
            ['code' => 'PHYSICAL', 'name' => 'Physical Book','icon' => 'feather-book-open',   'requires_shipping' => true],
        ];

        foreach ($formats as $f) {
            BookFormat::updateOrCreate(['code' => $f['code']], $f);
        }
    }
}