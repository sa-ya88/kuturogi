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
        $room = \App\Models\Room::first(); // 最初の部屋を取得

        if ($room) {
            $room->plans()->create([
                'name' => '【基本】創作会席・1泊2食付プラン',
                'price_per_person' => 15000,
                'description' => '当館一番人気のスタンダードなプランです。',
            ]);

            $room->plans()->create([
                'name' => '【贅沢】特選ブランド牛堪能プラン',
                'price_per_person' => 22000,
                'description' => '夕食に最高級のブランド牛をご用意いたします。',
            ]);
        }
    }
}
