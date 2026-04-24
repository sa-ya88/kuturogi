<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $room = \App\Models\Room::first();

        if ($room) {
            $room->plans()->createMany([
                [
                    'name' => '【基本】創作会席・1泊2食付プラン',
                    'price_per_person' => 4400,
                    'description' => '当館一番人気のスタンダードなプランです内。',
                    'images' => [
                        '/images/dinner1.webp','/images/onsen1.webp','/images/morning1.webp',
                    ],
                    'has_breakfast' => true,
                    'has_dinner' => true,
                ],
                [
                    'name' => '【贅沢】特選ブランド牛堪能プラン',
                    'price_per_person' => 6000,
                    'description' => '夕食に最高級のブランド牛をご用意いたします。',
                    'images' => [
                        '/images/dinner2-1.webp', '/images/dinner2-2.webp',
                        '/images/onsen1.webp','/images/morning1.webp',
                    ],
                    'has_breakfast' => true,
                    'has_dinner' => true,
                ],
                [
                    'name' => '【早割30】30日前までの予約でお得なプラン',
                    'price_per_person' => 3000,
                    'description' => '早めのご予約でお得に宿泊いただけるプランです。',
                    'images' => [
                        '/images/dinner1.webp','/images/onsen1.webp','/images/morning1.webp',
                    ],
                    'has_breakfast' => true,
                    'has_dinner' => true,
                ],
                [
                    'name' => '【朝食付き】40種類の和洋食ビュッフェプラン',
                    'price_per_person' => 2400,
                    'description' => '夕食無しで、お得に宿泊いただけるプランです。',
                    'images' => [
                        '/images/onsen1.webp','/images/scene1.webp',
                    ],
                    'has_breakfast' => false,
                    'has_dinner' => false,
                ],
                [
                    'name' => '【気軽な一人旅】素泊まりプラン',
                    'price_per_person' => 1200,
                    'description' => '朝夕食は無しで、お得に宿泊いただけるプランです。',
                    'images' => [
                        '/images/onsen1.webp','/images/scene1.webp',
                    ],
                    'has_breakfast' => false,
                    'has_dinner' => false,
                ],
            ]);
        }
    }
}
