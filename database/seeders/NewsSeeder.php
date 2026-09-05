<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        News::create([
            'title' => '春の特別会席のご案内',
            'content' => "旬の山菜をふんだんに使用した、この時期だけの特別なコースをご用意しました。\n\nご予約はお早めにお願いいたします。",
            'published_at' => now(),
        ]);

        News::create([
            'title' => '全館休館日のお知らせ',
            'content' => "設備点検のため、5月10日は休館とさせていただきます。\n\nご予約済みのお客様には、別途ご案内をお送りいたします。",
            'published_at' => now()->addDay(),
        ]);
    }
}
