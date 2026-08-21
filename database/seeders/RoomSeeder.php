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
        $rooms = [
            [
                'name' => 'スタンダード和室',
                'price_per_person' => 7000,
                'description' => '伝統的な日本の情緒を大切にした、落ち着きのあるスタンダードなお部屋です。',
                'features' => ['和室', '禁煙', '定員1〜4名'],
                'details' => [
                    'facilities' => ['バス', 'シャワー', 'トイレ', '冷暖房', 'テレビ', '冷蔵庫'],
                    'internet' => '全室Wi-Fi無料',
                    'smoking' => '全室禁煙（喫煙スペースあり）',
                    'amenities' => ['タオル', '歯ブラシ', '浴衣', 'ドライヤー', '石鹸類'],
                ],
                'images' => ['/images/room_1_1.webp'],
            ],
            [
                'name' => '和モダン客室「凛」- RIN -',
                'price_per_person' => 10000,
                'description' => '研ぎ澄まされた静寂と川のせせらぎが聴こえる、美しい景色のお部屋です。',
                'features' => ['和室', '禁煙', '定員1〜4名'],
                'details' => [
                    'facilities' => ['バス', 'シャワー', 'トイレ', '冷暖房', 'テレビ', '冷蔵庫'],
                    'internet' => '全室Wi-Fi無料',
                    'smoking' => '全室禁煙（喫煙スペースあり）',
                    'amenities' => ['タオル', '歯ブラシ', '浴衣', 'ドライヤー', '石鹸類'],
                ],
                'images' => ['/images/room_2_1.webp'],
            ],
            [
                'name' => '特別室「雅」- MIYABI -',
                'price_per_person' => 14000,
                'description' => '琉球畳の香りに包まれながら、洗練された現代的な空間でお寛ぎください。',
                'features' => ['露天風呂付', '和室', '禁煙', '定員2〜4名'],
                'details' => [
                    'facilities' => ['バス', 'シャワー', 'トイレ', '冷暖房', 'テレビ', '冷蔵庫'],
                    'internet' => '全室Wi-Fi無料',
                    'smoking' => '全室禁煙（喫煙スペースあり）',
                    'amenities' => ['タオル', '歯ブラシ', '浴衣', 'ドライヤー', '石鹸類'],
                ],
                'images' => ['/images/room_3_1.webp'],
            ],
            [
                'name' => '離れ「茜」- AKANE -',
                'price_per_person' => 18000,
                'description' => '当館最高級の広さを誇る、源泉掛け流し露天風呂付きの客室です。',
                'features' => ['和洋室', '禁煙', '定員2〜3名'],
                'details' => [
                    'facilities' => ['バス', 'シャワー', 'トイレ', '冷暖房', 'テレビ', '冷蔵庫'],
                    'internet' => '全室Wi-Fi無料',
                    'smoking' => '全室禁煙（喫煙スペースあり）',
                    'amenities' => ['タオル', '歯ブラシ', '浴衣', 'ドライヤー', '石鹸類'],
                ],
                'images' => ['/images/room_4_1.webp', '/images/room_4_2.webp', '/images/room_4_3.webp'],
            ],
        ];

        // 1件ずつ取り出して保存
        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }
    }
}
