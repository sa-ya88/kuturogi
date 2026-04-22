<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Room::create([
            'name' => '特別室「雅」',
            'description' => '源泉掛け流し露天風呂付きの、当館で最も贅沢なお部屋です。',
            'capacity' => 4,
            'image_url' => 'https://unsplash.com',
            'features' => ['露天風呂付', '和洋室', '禁煙'],
        ]);

        Room::create([
            'name' => '和モダン客室「凛」',
            'description' => '琉球畳とベッドを組み合わせた、モダンで落ち着いた空間。',
            'capacity' => 2,
            'image_url' => 'https://unsplash.com',
            'features' => ['和室', 'シモンズ製ベッド', '禁煙'],
        ]);
    }
}
